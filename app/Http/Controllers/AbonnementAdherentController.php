<?php

namespace App\Http\Controllers;

use App\Models\AbonnementAdherent;
use App\Models\Complexe;
use App\Models\ReglementAbonnement;
use App\Models\TypeAbonnementAdherent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'date_debut' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();
        $type = TypeAbonnementAdherent::findOrFail($request->type_abonnement_id);

        $existing = AbonnementAdherent::where('user_id', $user->id)
            ->where('type_abonnement_id', $type->id)
            ->where('statut', 'actif')
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Vous avez déjà un abonnement actif pour cette formule.'], 422);
        }

        $dateDebut = Carbon::parse($request->date_debut)->toDateString();
        $dateFin = Carbon::parse($dateDebut)->addMonths($type->nb_mois)->toDateString();

        $montantVente = $type->tarif;
        $remise = 0;
        $montantApres = $montantVente * (1 - $remise / 100);

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
            'paye' => false,
            'reste_a_payer' => $montantApres,
        ]);

        return response()->json(['success' => true, 'message' => 'Abonnement créé.', 'data' => $abonnement->load(['typeAbonnement', 'complexe', 'reglements', 'user'])], 201);
    }

    public function mesAbonnements(): JsonResponse
    {
        $user = auth('api')->user();
        $subs = AbonnementAdherent::with(['typeAbonnement', 'complexe', 'reglements'])
            ->where('user_id', $user->id)
            ->orderByDesc('date_debut')
            ->get();

        return response()->json(['success' => true, 'data' => $subs]);
    }

    public function show($id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with(['typeAbonnement', 'complexe', 'reglements', 'user'])->findOrFail($id);

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
    public function cancel($id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);

        // Verify ownership
        if ($sub->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Only allow cancellation if not already cancelled
        if ($sub->statut === 'annule') {
            return response()->json(['success' => false, 'message' => 'Déjà annulé.'], 422);
        }

        $sub->update(['statut' => 'annule']);

        return response()->json(['success' => true, 'message' => 'Abonnement annulé.']);
    }

    /**
     * Client-side pay subscription
     */
    public function pay(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);

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
            'reference' => ['nullable', 'string', 'max:30', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
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

        return response()->json(['success' => true, 'message' => 'Paiement effectué.', 'data' => $sub->fresh()]);
    }

    public function destroy($id): JsonResponse
    {
        $user = auth('api')->user();
        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);

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
            }

            $types = $query->with('complexe')
                ->withCount(['abonnements as abonnements_count'])
                ->get();

            return response()->json(['success' => true, 'data' => $types, 'count' => $types->count()]);
        } catch (\Exception $e) {
            \Log::error('Error in adminTypes: '.$e->getMessage(), [
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
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $complexe = Complexe::findOrFail($request->complexe_id);
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
        ]);
        $type->load('complexe');

        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function adminUpdateType(Request $request, $id): JsonResponse
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
            'active' => 'sometimes|boolean',
        ];

        if ($request->has('complexe_id')) {
            $rules['complexe_id'] = 'required|exists:complexes,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['nom', 'description', 'nb_mois', 'tarif', 'prix_unitaire', 'niveau_sportif_cible', 'sport_cible', 'avantages', 'active']);

        if ($request->has('complexe_id')) {
            $complexe = Complexe::findOrFail($request->complexe_id);
            if ($user->isGerant() && $complexe->owner_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Forbidden. Complexe not found.'], 403);
            }
            $data['complexe_id'] = $complexe->id;
        }

        $type->update($data);

        return response()->json(['success' => true, 'data' => $type->fresh(['complexe'])]);
    }

    public function adminDeleteType($id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $type = TypeAbonnementAdherent::findOrFail($id);
        if ($user->isGerant() && $type->complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Real delete only if there are no dependencies
        if ($type->abonnements()->exists() || $type->detailsAbonnements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette formule car des adhérents y sont abonnés ou elle possède des détails d\'abonnement liés. Désactivez-la à la place.',
            ], 422);
        }

        $type->delete();

        return response()->json(['success' => true, 'message' => 'Formule d\'abonnement supprimée.']);
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
            if ($user->isGerant()) {
                $query->whereHas('complexe', fn ($q) => $q->where('owner_id', $user->id));
            }

            $subs = $query->orderByDesc('date_debut')->get();

            return response()->json(['success' => true, 'data' => $subs, 'count' => $subs->count()]);
        } catch (\Exception $e) {
            \Log::error('Error in adminAbonnements: '.$e->getMessage(), [
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
            \Log::error('Error in AbonnementAdherentController@stats: '.$e->getMessage(), [
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

    public function adminConfirmPayment(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);
        if ($user->isGerant() && $sub->complexe->owner_id !== $user->id) {
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

        $sub->update([
            'paye' => true,
            'statut' => 'actif',
            'reste_a_payer' => 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Paiement confirmé.', 'data' => $sub->fresh()]);
    }

    public function adminCancel($id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with('complexe')->findOrFail($id);
        if ($user->isGerant() && $sub->complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub->update(['statut' => 'annule']);

        return response()->json(['success' => true, 'message' => 'Abonnement annulé.']);
    }

    public function adminDestroy($id): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user->isGerant() && ! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $sub = AbonnementAdherent::with(['complexe', 'reglements'])->findOrFail($id);
        if ($user->isGerant() && $sub->complexe->owner_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        if ($sub->statut === 'actif') {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un abonnement actif.'], 422);
        }

        // Preserve accounting trace if any payment was ever recorded
        $hasPayment = $sub->paye || $sub->reglements()->count() > 0;

        if ($hasPayment) {
            $sub->delete(); // soft-delete — trace comptable préservée
            $message = 'Abonnement archivé (supprimé de l\'affichage).';
        } else {
            $sub->forceDelete(); // aucun paiement, suppression réelle
            $message = 'Abonnement supprimé définitivement.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }
}
