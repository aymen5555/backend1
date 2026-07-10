<?php

namespace App\Http\Controllers;

use App\Models\AbonnementAdherent;
use App\Models\Complexe;
use App\Models\ReglementAbonnement;
use App\Models\TypeAbonnementAdherent;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Refund;

class AbonnementAdherentController extends Controller
{
    public function typesDisponibles(Request $request): JsonResponse
    {
        $query = TypeAbonnementAdherent::where('active', true);

        if ($request->filled('complexe_id')) {
            $validator = Validator::make($request->all(), [
                'complexe_id' => 'exists:complexes,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
            }

            $query->where('complexe_id', $request->complexe_id);
        }

        $types = $query->with('complexe')->get();

        return response()->json(['success' => true, 'data' => $types]);
    }

    public function souscrire(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type_abonnement_id' => 'required|exists:type_abonnement_adherent,id',
            'modalite_paiement' => 'required|in:especes,carte',
            'date_debut' => 'required|date',
            // Accept both legacy TXN-... codes and Stripe pi_... payment intent IDs
            'reference' => ['nullable', 'string', 'max:255', 'regex:/^(TXN-\d{4}-\d{3,8}|pi_[a-zA-Z0-9_]+)$/i'],
        ]);

        if ($validator->fails()) {
            $refundInfo = null;
            // If a Stripe payment intent was provided and frontend already charged,
            // attempt to refund the payment so clients are not charged for invalid requests.
            if ($request->filled('reference') && str_starts_with($request->reference ?? '', 'pi_')) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));

                    // Check existing refunds for this payment_intent to avoid duplicate attempts
                    $existing = Refund::all(['payment_intent' => $request->reference]);
                    if (! empty($existing->data)) {
                        $first = $existing->data[0];
                        $refundInfo = [
                            'already_refunded' => true,
                            'refund_id' => $first->id ?? null,
                            'status' => $first->status ?? null,
                        ];
                        Log::info('Auto-refund skipped because refund already exists', ['reference' => $request->reference, 'refund' => $refundInfo]);
                    } else {
                        $refund = Refund::create(['payment_intent' => $request->reference]);
                        $refundInfo = [
                            'already_refunded' => false,
                            'refund_id' => $refund->id ?? null,
                            'status' => $refund->status ?? null,
                        ];
                        Log::info('Auto-refund issued due to validation failure', ['reference' => $request->reference, 'refund' => $refundInfo]);
                    }
                } catch (\Exception $e) {
                    Log::error('Auto-refund failed after validation error', ['reference' => $request->reference, 'error' => $e->getMessage()]);
                    $refundInfo = ['error' => $e->getMessage()];
                }
            }

            $body = ['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()];
            if ($refundInfo !== null) {
                $body['refund'] = $refundInfo;
            }

            return response()->json($body, 422);
        }

        $user = auth('api')->user();
        $type = TypeAbonnementAdherent::findOrFail($request->type_abonnement_id);
        /** @var TypeAbonnementAdherent $type */

        // Normalize incoming date to the application timezone and compare calendar date
        $dateDebut = Carbon::parse($request->date_debut)->setTimezone(config('app.timezone'))->toDateString();
        // Ensure date_debut is not before today in server/app timezone
        if (Carbon::parse($dateDebut)->lt(Carbon::today())) {
            $refundInfo = null;
            // If a Stripe payment intent was provided and frontend already charged,
            // attempt to refund the payment to avoid orphaned charges.
            if ($request->filled('reference') && str_starts_with($request->reference ?? '', 'pi_')) {
                try {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    $existing = Refund::all(['payment_intent' => $request->reference]);
                    if (! empty($existing->data)) {
                        $first = $existing->data[0];
                        $refundInfo = [
                            'already_refunded' => true,
                            'refund_id' => $first->id ?? null,
                            'status' => $first->status ?? null,
                        ];
                        Log::info('Auto-refund skipped because refund already exists', ['reference' => $request->reference, 'refund' => $refundInfo, 'date_debut' => $dateDebut]);
                    } else {
                        $refund = Refund::create(['payment_intent' => $request->reference]);
                        $refundInfo = [
                            'already_refunded' => false,
                            'refund_id' => $refund->id ?? null,
                            'status' => $refund->status ?? null,
                        ];
                        Log::info('Auto-refund issued due to date_debut before today', ['reference' => $request->reference, 'refund' => $refundInfo, 'date_debut' => $dateDebut]);
                    }
                } catch (\Exception $e) {
                    Log::error('Auto-refund failed for old date_debut', ['reference' => $request->reference, 'error' => $e->getMessage()]);
                    $refundInfo = ['error' => $e->getMessage()];
                }
            }

            $body = ['success' => false, 'message' => 'Validation failed.', 'errors' => ['date_debut' => ['The date debut field must be a date after or equal to today.']]];
            if ($refundInfo !== null) {
                $body['refund'] = $refundInfo;
            }

            return response()->json($body, 422);
        }
        $dateFin = Carbon::parse($dateDebut)->addMonths($type->nb_mois)->toDateString();

        $hasOverlappingActive = AbonnementAdherent::where('user_id', $user->id)
            ->where('type_abonnement_id', $type->id)
            ->where('statut', 'actif')
            ->whereDate('date_fin', '>=', $dateDebut)
            ->exists();

        if ($hasOverlappingActive) {
            return response()->json(['success' => false, 'message' => 'Vous avez déjà un abonnement actif pour cette formule.'], 422);
        }
        $dateFin = Carbon::parse($dateDebut)->addMonths($type->nb_mois)->toDateString();

        $montantVente = $type->tarif;

        // Calculate pricing using PricingService based on plan discount
        $pricingService = new PricingService();
        $pricing = $pricingService->calculateAbonnementPricing($type, $montantVente);
        $remise = $pricing['remise'];
        $montantApres = $pricing['montant_apres_remise'];

        // If card payment with a Stripe reference, mark as paid immediately
        $paidByCard = $request->modalite_paiement === 'carte' && $request->filled('reference');

        $abonnement = AbonnementAdherent::create([
            'user_id' => $user->id,
            'complexe_id' => $type->complexe_id,
            'type_abonnement_id' => $type->id,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'montant_vente' => $montantVente,
            'remise' => $remise,
            'montant_apres_remise' => $montantApres,
            'statut' => 'actif',
            'paye' => $paidByCard,
            'reste_a_payer' => $paidByCard ? 0 : $montantApres,
            'refund_status' => 'not_requested',
            'stripe_payment_intent_id' => ($request->modalite_paiement === 'carte' && strpos($request->reference ?? '', 'pi_') === 0) ? $request->reference : null,
        ]);

        // Record payment settlement immediately when paying by card on creation
        if ($paidByCard) {
            ReglementAbonnement::create([
                'abonnement_id' => $abonnement->id,
                'montant' => $montantApres,
                'date_reglement' => Carbon::now()->toDateString(),
                'modalite' => 'carte',
                'reference' => $request->reference,
                'encaisse' => true,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Abonnement créé.', 'data' => $abonnement->load(['typeAbonnement', 'complexe', 'reglements', 'user'])], 201);
    }

    public function mesAbonnements(): JsonResponse
    {
        $user = auth('api')->user();
        $subs = AbonnementAdherent::with(['typeAbonnement', 'complexe', 'reglements'])
            ->where('user_id', $user->id)
            ->orderByDesc('date_debut')
            ->get();

        $filtered = $subs->groupBy(fn ($sub) => $sub->complexe_id . '_' . $sub->type_abonnement_id)
            ->flatMap(function ($group) {
                $activeSubs = $group->where('statut', 'actif');
                if ($activeSubs->count() > 1) {
                    $latestActive = $activeSubs->sortByDesc('date_debut')->first();
                    return $group->reject(fn ($sub) => $sub->statut === 'actif' && $sub->id !== $latestActive->id);
                }
                return $group;
            })
            ->sortByDesc('date_debut')
            ->values();

        return response()->json(['success' => true, 'data' => $filtered]);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with(['typeAbonnement', 'complexe', 'reglements', 'user'])->findOrFail($id);
        /** @var AbonnementAdherent $sub */

        if ($sub->user_id !== $user->id && ! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if ($user->isGerant() && $sub->complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return response()->json(['success' => true, 'data' => $sub]);
    }

    /**
     * Client-side cancel subscription
     */
    public function cancel(int $id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with(['complexe', 'typeAbonnement', 'user'])->findOrFail($id);
        /** @var AbonnementAdherent $sub */

        // Verify ownership
        if ($sub->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Only allow cancellation if not already cancelled
        if ($sub->statut === 'annule') {
            return response()->json(['success' => false, 'message' => 'Déjà annulé.'], 422);
        }

        // Mark as cancelled
        $updateData = ['statut' => 'annule'];

        // For card payments, set refund_status to pending (will require admin confirmation)
        if ($sub->paye) {
            $updateData['refund_status'] = 'pending';
        }

        $sub->update($updateData);

        if ($sub->user) {
            $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub));
        }

        // If this cancellation requires a refund (paid by card), notify complex owner and admins
        if (($sub->paye ?? false) && ($sub->refund_status === 'pending')) {
            /** @var User|null $owner */
            /** @var User|null $owner */
            $owner = $sub->complexe?->owner;
            $notificationMessage = 'Demande de remboursement en attente pour l\'abonnement ' . (optional($sub->typeAbonnement)->nom ?? '') . ' (' . (optional($sub->complexe)->name ?? '') . ').';

            $notifiableUsers = collect();
            if ($owner) {
                $notifiableUsers->push($owner);
            }
            $notifiableUsers = $notifiableUsers->merge(User::whereIn('role', ['super_admin', 'admin'])->get());
            $notifiableUsers->unique('id')->each(function (User $user) use ($sub, $notificationMessage) {
                $user->notify(new \App\Notifications\AbonnementStatusChanged($sub, $notificationMessage));
            });
        }

        AuditService::cancel($user, 'AbonnementAdherent', $sub->id, 'Client cancelled subscription');

        return response()->json(['success' => true, 'message' => 'Abonnement annulé.']);
    }

    /**
     * Client-side pay subscription
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with(['complexe', 'typeAbonnement', 'user'])->findOrFail($id);
        /** @var AbonnementAdherent $sub */

        // Verify ownership
        if ($sub->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Already paid?
        if ($sub->paye) {
            return response()->json(['success' => false, 'message' => 'Déjà payé.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            // Accept both legacy TXN-... codes and Stripe pi_... payment intent IDs
            'reference' => ['nullable', 'string', 'max:255', 'regex:/^(TXN-\d{4}-\d{3,8}|pi_[a-zA-Z0-9_]+)$/i'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        ReglementAbonnement::create([
            'abonnement_id' => $sub->id,
            'montant' => $sub->reste_a_payer ?? $sub->montant_apres_remise,
            'date_reglement' => Carbon::now()->toDateString(),
            'modalite' => $request->modalite_paiement,
            'reference' => $request->reference ?? null,
            'encaisse' => true,
        ]);

        $sub->update([
            'paye' => true,
            'statut' => 'actif',
            'reste_a_payer' => 0,
        ]);

        AuditService::payment($user, 'AbonnementAdherent', $sub->id, $sub->montant_apres_remise, $request->modalite_paiement);

        if ($sub->user) {
            $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub, "Le paiement de votre abonnement " . (optional($sub->typeAbonnement)->nom ?? 'Adhérent') . " a été enregistré avec succès."));
        }

        return response()->json(['success' => true, 'message' => 'Paiement effectué.', 'data' => $sub->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);
        /** @var AbonnementAdherent $sub */

        if ($sub->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if ($sub->statut === 'actif') {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un abonnement actif.'], 422);
        }

        $sub->update(['statut' => 'annule']);

        return response()->json(['success' => true, 'message' => 'Abonnement conservé et marqué comme annulé.']);
    }

    public function adminTypes(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }
            if (! $user->isGerant() && ! $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }

            $query = TypeAbonnementAdherent::query();
            if ($user->isGerant()) {
                $query->whereHas('complexe', fn ($q) => $q->where('owner_id', $user->id));
            } else {
                $req = request();
                if ($req->filled('complexe_id')) {
                    $validator = Validator::make($req->all(), [
                        'complexe_id' => 'exists:complexes,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
                    }

                    $query->where('complexe_id', $req->complexe_id);
                }
            }

            $types = $query->with('complexe')
                ->withCount(['abonnements as abonnements_count'])
                ->get();

            return response()->json(['success' => true, 'data' => $types, 'count' => $types->count()]);
        } catch (\Exception $e) {
            Log::error('Error in adminTypes: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth('api')->id() ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading subscription types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminStoreType(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'nom' => 'required|string|max:255',
            'nb_mois' => 'required|integer|min:1',
            'tarif' => 'required|numeric|min:0',
            'prix_unitaire' => 'required|numeric|min:0',
            'niveau_sportif_cible' => 'required|in:debutant,intermediaire,expert,tous',
            'sport_cible' => 'nullable|string',
            'avantages' => 'nullable|array',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $complexeId = (int) $request->complexe_id;
        $complexe = Complexe::findOrFail($complexeId);
        /** @var Complexe $complexe */
        if ($user->isGerant() && $complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden. Complexe not found.'], 403);
        }

        $type = TypeAbonnementAdherent::create([
            'complexe_id' => $request->complexe_id,
            'nom' => $request->nom,
            'description' => $request->description ?? null,
            'nb_mois' => $request->nb_mois,
            'tarif' => $request->tarif,
            'prix_unitaire' => $request->prix_unitaire,
            'niveau_sportif_cible' => $request->niveau_sportif_cible,
            'sport_cible' => $request->sport_cible ?? null,
            'avantages' => $request->avantages ?? null,
            'active' => true,
            'discount_percentage' => $request->discount_percentage ?? null,
        ]);
        $type->load('complexe');

        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function adminUpdateType(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $type = TypeAbonnementAdherent::findOrFail($id);
        if ($user->isGerant() && $type->complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $rules = [
            'nom' => 'sometimes|required|string|max:255',
            'nb_mois' => 'sometimes|required|integer|min:1',
            'tarif' => 'sometimes|required|numeric|min:0',
            'prix_unitaire' => 'sometimes|required|numeric|min:0',
            'niveau_sportif_cible' => 'sometimes|required|in:debutant,intermediaire,expert,tous',
            'sport_cible' => 'nullable|string',
            'avantages' => 'nullable|array',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'active' => 'sometimes|boolean',
        ];

        if ($request->has('complexe_id')) {
            $rules['complexe_id'] = 'required|exists:complexes,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nom', 'description', 'nb_mois', 'tarif', 'prix_unitaire', 'niveau_sportif_cible', 'sport_cible', 'avantages', 'active', 'discount_percentage']);

        if ($request->has('complexe_id')) {
            $complexeId = (int) $request->complexe_id;
            $complexe = Complexe::findOrFail($complexeId);
            if ($user->isGerant() && $complexe->owner_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden. Complexe not found.'], 403);
            }
            $data['complexe_id'] = $complexe->id;
        }

        $type->update($data);

        return response()->json(['success' => true, 'data' => $type->fresh(['complexe'])]);
    }

    public function adminDeleteType(int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $type = TypeAbonnementAdherent::findOrFail($id);
        if ($user->isGerant() && $type->complexe?->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if (AbonnementAdherent::withTrashed()->where('type_abonnement_id', $type->id)->exists() || $type->detailsAbonnements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette formule car des adhérents y sont abonnés ou elle possède des détails d\'abonnement liés. Désactivez-la à la place.',
            ], 422);
        }

        $type->update(['active' => false]);

        return response()->json(['success' => true, 'message' => 'Formule d\'abonnement désactivée.']);
    }

    public function adminAbonnements(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }
            if (! $user->isGerant() && ! $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
            }

            $query = AbonnementAdherent::with(['user', 'typeAbonnement.complexe', 'complexe', 'reglements']);

            // Gerant: always scope to their complexe (defensive and deterministic)
            if ($user->isGerant()) {
                $complexeId = Complexe::where('owner_id', $user->id)->value('id');

                // Gérant without an assigned complexe → return empty list
                if (! $complexeId) {
                    return response()->json(['success' => true, 'data' => [], 'count' => 0]);
                }

                $query->where('complexe_id', $complexeId);
            } else {
                // Superadmin: optional filtering by `complexe_id` query param
                $req = request();
                if ($req->filled('complexe_id')) {
                    $validator = Validator::make($req->all(), [
                        'complexe_id' => 'exists:complexes,id',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
                    }

                    $query->where('complexe_id', $req->complexe_id);
                }
            }

            $subs = $query->orderByDesc('date_debut')->get();

            return response()->json(['success' => true, 'data' => $subs, 'count' => $subs->count()]);
        } catch (\Exception $e) {
            Log::error('Error in adminAbonnements: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth('api')->id() ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading subscriptions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aggregated stats for admin abonnements page.
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
            }

            $query = TypeAbonnementAdherent::query();
            $abQuery = AbonnementAdherent::query();

            if ($user->isGerant()) {
                $complexeId = Complexe::where('owner_id', $user->id)->value('id');

                // Gérant without an assigned complexe or complexe not found → return zeroed stats
                if (! $complexeId) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'formules' => 0,
                            'actifs' => 0,
                            'complexes' => 0,
                            'en_attente_paiement' => 0,
                            'total_du' => 0,
                        ],
                    ]);
                }

                $query->where('complexe_id', $complexeId);
                $abQuery->where('complexe_id', $complexeId);
            }

            $formules = $query->where('active', true)->count();
            $actifs = (clone $abQuery)->where('statut', 'actif')->count();
            $enAttente = (clone $abQuery)->where('paye', false)->where('statut', 'actif')->count();
            $totalDu = (clone $abQuery)->where('paye', false)->where('statut', 'actif')->sum('reste_a_payer');
            $complexesCount = $user->isGerant() ? 1 : Complexe::where('is_active', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'formules' => $formules,
                    'actifs' => $actifs,
                    'complexes' => $complexesCount,
                    'en_attente_paiement' => $enAttente,
                    'total_du' => $totalDu,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in AbonnementAdherentController@stats: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth('api')->id() ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminConfirmPayment(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with(['complexe', 'typeAbonnement', 'user'])->findOrFail($id);
        if ($user->isGerant() && $sub->complexe?->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            'montant' => 'required|numeric|min:0',
            'reference' => ['nullable', 'string', 'max:30', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        ReglementAbonnement::create([
            'abonnement_id' => $sub->id,
            'montant' => $request->montant,
            'date_reglement' => Carbon::now()->toDateString(),
            'modalite' => $request->modalite_paiement,
            'reference' => $request->reference ?? null,
            'encaisse' => true,
        ]);

        // decrement remaining amount, mark paid only if fully settled
        $newReste = max(0, ($sub->reste_a_payer ?? $sub->montant_apres_remise) - (float) $request->montant);
        $isPaid = $newReste <= 0.0001;

        $sub->update([
            'paye' => $isPaid,
            'statut' => 'actif',
            'reste_a_payer' => $newReste,
        ]);

        if ($sub->user) {
            $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub, "Le paiement de votre abonnement " . (optional($sub->typeAbonnement)->nom ?? 'Adhérent') . " a été enregistré avec succès."));
        }

        return response()->json(['success' => true, 'message' => 'Paiement confirmé.', 'data' => $sub->fresh()]);
    }

    public function adminCancel(int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with(['complexe', 'typeAbonnement', 'user'])->findOrFail($id);
        if ($user->isGerant() && $sub->complexe?->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub->update(['statut' => 'annule']);

        if ($sub->user) {
            $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub));
        }

        return response()->json(['success' => true, 'message' => 'Abonnement annulé.']);
    }

    /**
     * Admin-side refund approval for cancelled subscriptions
     */
    public function confirmerRemboursement(int $id): JsonResponse
    {
        $user = auth('api')->user();

        // Intentionally avoid logging sensitive authorization headers or bearer tokens here.

        $sub = AbonnementAdherent::with(['complexe', 'typeAbonnement', 'user'])->findOrFail($id);
        /** @var AbonnementAdherent $sub */

        // Admins can always review refunds. Gerants can only process refunds for their own complexe.
        if (! $user->isAdmin() && ! ($user->isGerant() && optional($sub->complexe)->owner_id === $user->id)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Check refund status is pending
        if ($sub->refund_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Le statut du remboursement doit être "en attente".',
            ], 422);
        }

        // Use transaction to ensure atomicity
        DB::transaction(function () use ($sub, $user) {
            // Set Stripe API key
            Stripe::setApiKey(config('services.stripe.secret'));

            if (! $sub->stripe_payment_intent_id) {
                // Manual refund (no Stripe involved)
                $sub->update([
                    'refund_status' => 'succeeded',
                    'refund_reference' => 'manual',
                ]);

                AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'succeeded', 'method' => 'manual']);
            } else {
                // Stripe refund - call the refund API
                try {
                    $refundResponse = Refund::create([
                        'payment_intent' => $sub->stripe_payment_intent_id,
                    ]);

                    $sub->update([
                        'refund_status' => 'succeeded',
                        'refund_reference' => $refundResponse->id,
                    ]);

                    AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'succeeded', 'reference' => $refundResponse->id, 'method' => 'stripe']);

                    Log::info('Stripe refund created for subscription', [
                        'subscription_id' => $sub->id,
                        'refund_id' => $refundResponse->id,
                        'amount' => $sub->montant_apres_remise,
                    ]);
                } catch (\Exception $e) {
                    // Stripe refund failed
                    $sub->update([
                        'refund_status' => 'failed',
                        'refund_reference' => null,
                    ]);

                    AuditService::refund($user, 'AbonnementAdherent', $sub->id, ['status' => 'failed', 'error' => $e->getMessage()]);

                    Log::error('Stripe refund failed for subscription', [
                        'subscription_id' => $sub->id,
                        'error' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            }
        });

        $sub = $sub->fresh();
        if ($sub->user) {
            $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub, 'Votre remboursement a été approuvé et traité.'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Remboursement validé.',
            'data' => $sub,
        ]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with(['complexe', 'reglements'])->findOrFail($id);
        /** @var AbonnementAdherent $sub */
        if ($user->isGerant() && optional($sub->complexe)->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if ($sub->statut === 'actif') {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un abonnement actif.'], 422);
        }

        // Always soft-delete to preserve audit trail — even unpaid subscriptions show in archives
        AuditService::delete($user, 'AbonnementAdherent', $sub->id, ['statut' => $sub->statut, 'user_id' => $sub->user_id]);
        $sub->delete();
        $message = 'Abonnement archivé (supprimé de l\'affichage).';

        return response()->json(['success' => true, 'message' => $message]);
    }
}
