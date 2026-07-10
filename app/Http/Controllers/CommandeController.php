<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Complexe;
use App\Models\LigneCommande;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class CommandeController extends Controller
{
    private const ACCESS_DENIED_MESSAGE = 'Accès interdit.';

    private function authorizeGerant(Complexe $complexe): void
    {
        $user = auth('api')->user();
        if ($user->role === 'gerant' && $complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'modalite_paiement' => 'required|in:especes,carte',
            'payment_confirmed' => 'nullable|boolean',
            'stripe_payment_intent_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|exists:produits,id',
            'items.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        $complexeId = $request->complexe_id;
        $items = collect($request->items);

        $produitIds = $items->pluck('produit_id')->unique();
        $produits = Produit::whereIn('id', $produitIds)
            ->where('complexe_id', $complexeId)
            ->where('actif', true)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $produit = $produits->get($item['produit_id']);
            if (! $produit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit introuvable: '.$item['produit_id'],
                ], 422);
            }

            $stock = Stock::where('produit_id', $produit->id)->first();
            if (! $stock || $stock->quantite_disponible < $item['quantite']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour '.$produit->nom,
                ], 422);
            }
        }

        try {
            return DB::transaction(function () use ($request, $user, $items, $produits, $complexeId) {
                $lignes = [];

                foreach ($items as $item) {
                    $produit = $produits->get($item['produit_id']);
                    $stock = Stock::where('produit_id', $produit->id)->lockForUpdate()->first();
                    if (! $stock || $stock->quantite_disponible < $item['quantite']) {
                        throw new \Exception('Stock insuffisant pour '.$produit->nom);
                    }
                    $stock->decrement('quantite_disponible', $item['quantite']);

                    $prixUnitaire = $produit->prix;
                    $sousTotal = $prixUnitaire * $item['quantite'];

                    $lignes[] = [
                        'produit_id' => $produit->id,
                        'quantite' => $item['quantite'],
                        'prix_unitaire' => $prixUnitaire,
                        'sous_total' => $sousTotal,
                    ];
                }

                $montantTotal = array_sum(array_column($lignes, 'sous_total'));

                $statutPaiement = 'non_paye';
                if ($request->modalite_paiement === 'carte' && (bool) $request->boolean('payment_confirmed', false)) {
                    $statutPaiement = 'paye';
                }

                $commandeData = [
                    'user_id' => $user->id,
                    'complexe_id' => $complexeId,
                    'statut' => 'en_attente',
                    'statut_paiement' => $statutPaiement,
                    'modalite_paiement' => $request->modalite_paiement,
                    'notes' => $request->notes,
                    'montant_total' => $montantTotal,
                ];

                if ($request->filled('stripe_payment_intent_id')) {
                    $commandeData['stripe_payment_intent_id'] = $request->stripe_payment_intent_id;
                }

                $commande = Commande::create($commandeData);

                foreach ($lignes as $ligne) {
                    $ligne['commande_id'] = $commande->id;
                    LigneCommande::create($ligne);
                }

                $commande->load(['lignes.produit', 'complexe.owner']);

                $commande->lignes->each(function ($ligne) {
                    $ligne->produit_nom = $ligne->produit->nom;
                });

                // Notify complex owner (gérant)
                $owner = $commande->complexe?->owner;
                if ($owner) {
                    $owner->notify(new \App\Notifications\OrderStatusChanged(
                        $commande,
                        "Nouvelle commande #{$commande->id} reçue d'un montant de {$montantTotal} TND."
                    ));
                }

                return response()->json([
                    'success' => true,
                    'data' => $commande,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createPaymentIntent(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }
        // SECURITY: Do not accept an `amount` from the client. Determine amount server-side
        // Accept a `reservation_id` to compute the authoritative TND price and convert to Stripe currency.
        // Accept either a reservation_id OR a shop items payload { items: [{produit_id, quantite}], complexe_id }
        $hasReservation = $request->filled('reservation_id');
        $hasItems = is_array($request->input('items')) && count($request->input('items')) > 0;
        $hasSubscription = $request->filled('type_abonnement_id');
        $hasAbonnement = $request->filled('abonnement_id');

        if (! $hasReservation && ! $hasItems && ! $hasSubscription) {
            return response()->json(['success' => false, 'message' => 'reservation_id, items ou type_abonnement_id requis.'], 422);
        }

        $tndAmount = 0;
        $contextComplexeId = null;

        if ($hasReservation) {
            $reservation = \App\Models\Reservation::with(['terrain.complexe', 'user'])->findOrFail($request->reservation_id);
            if ($reservation->user_id !== $user->id && ! $user->isAdmin() && ! $user->isGerant()) {
                return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
            }
            $tndAmount = ($reservation->statut_paiement === 'paye' ? $reservation->montant_paye : $reservation->tarif_calcule) ?? 0;
            $contextComplexeId = $reservation->terrain?->complexe_id ?? null;
        } elseif ($hasItems) {
            $items = $request->input('items');
            $produitIds = collect($items)->pluck('produit_id')->unique()->toArray();
            $produits = \App\Models\Produit::whereIn('id', $produitIds)->get()->keyBy('id');

            foreach ($items as $item) {
                $prod = $produits->get($item['produit_id']);
                if (! $prod) {
                    return response()->json(['success' => false, 'message' => 'Produit introuvable: '.$item['produit_id']], 422);
                }
                $qty = max(1, (int) ($item['quantite'] ?? 1));
                $tndAmount += ($prod->prix * $qty);
                $contextComplexeId = $prod->complexe_id;
            }
        } elseif ($hasSubscription) {
            $typeId = $request->input('type_abonnement_id');
            $dateDebut = $request->input('date_debut');
            $type = \App\Models\TypeAbonnementAdherent::findOrFail($typeId);
            // Reuse PricingService logic from subscription controller
            $pricingService = new \App\Services\PricingService();
            $pricing = $pricingService->calculateAbonnementPricing($type, $type->tarif);
            $tndAmount = $pricing['montant_apres_remise'] ?? $type->tarif;
            $contextComplexeId = $type->complexe_id;
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Stripe account currency (server-configured)
            $stripeCurrency = strtolower(config('services.stripe.currency', 'eur'));

            // Convert TND -> EUR using admin-configurable single-value rate if Stripe currency is EUR
            if ($stripeCurrency === 'tnd') {
                // If account supports TND, charge in TND (amount in millimes)
                $amountForStripe = (int) round($tndAmount * 1000);
            } else {
                $rate = (float) config('services.fx.tnd_to_eur', 0.32);
                $amountEur = round($tndAmount * $rate, 2);
                $amountForStripe = (int) round($amountEur * 100);
            }

            if ($amountForStripe <= 0) {
                return response()->json(['success' => false, 'message' => 'Montant invalide pour le paiement.'], 422);
            }

            $metadata = ['user_id' => $user->id];
            if ($hasReservation && isset($reservation)) {
                $metadata['type'] = 'reservation';
                $metadata['reservation_id'] = $reservation->id;
            } elseif ($hasItems) {
                $metadata['type'] = 'commande';
            } elseif ($hasSubscription && isset($type)) {
                $metadata['type'] = 'abonnement_type';
                $metadata['type_abonnement_id'] = $type->id;
            } elseif ($hasAbonnement && isset($ab)) {
                $metadata['type'] = 'abonnement';
                $metadata['abonnement_id'] = $ab->id;
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountForStripe,
                'currency' => $stripeCurrency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $metadata,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'clientSecret' => $paymentIntent->client_secret,
                    'paymentIntentId' => $paymentIntent->id,
                    'computed' => [
                        'amount' => $amountForStripe,
                        'currency' => $stripeCurrency,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer le paiement Stripe : '.$e->getMessage(),
            ], 500);
        }
    }

    public function previewPayment(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }
        // Accept either reservation_id OR items+complexe_id for preview
        // Accept reservation, items (shop) or subscription type for preview
        $hasReservation = $request->filled('reservation_id');
        $hasItems = is_array($request->input('items')) && count($request->input('items')) > 0;
        $hasSubscription = $request->filled('type_abonnement_id');

        if (! $hasReservation && ! $hasItems && ! $hasSubscription) {
            return response()->json(['success' => false, 'message' => 'reservation_id, items ou type_abonnement_id requis.'], 422);
        }

        $tndAmount = 0;

        if ($hasReservation) {
            $reservation = \App\Models\Reservation::with(['terrain.complexe', 'user'])->findOrFail($request->reservation_id);
            if ($reservation->user_id !== $user->id && ! $user->isAdmin() && ! $user->isGerant()) {
                return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
            }
            $tndAmount = ($reservation->statut_paiement === 'paye' ? $reservation->montant_paye : $reservation->tarif_calcule) ?? 0;
        } elseif ($hasItems) {
            $items = $request->input('items');
            $produitIds = collect($items)->pluck('produit_id')->unique()->toArray();
            $produits = \App\Models\Produit::whereIn('id', $produitIds)->get()->keyBy('id');

            foreach ($items as $item) {
                $prod = $produits->get($item['produit_id']);
                if (! $prod) {
                    return response()->json(['success' => false, 'message' => 'Produit introuvable: '.$item['produit_id']], 422);
                }
                $qty = max(1, (int) ($item['quantite'] ?? 1));
                $tndAmount += ($prod->prix * $qty);
                $contextComplexeId = $prod->complexe_id;
            }
        } elseif ($hasSubscription) {
            $typeId = $request->input('type_abonnement_id');
            $type = \App\Models\TypeAbonnementAdherent::findOrFail($typeId);
            $pricingService = new \App\Services\PricingService();
            $pricing = $pricingService->calculateAbonnementPricing($type, $type->tarif);
            $tndAmount = $pricing['montant_apres_remise'] ?? $type->tarif;
            $contextComplexeId = $type->complexe_id;
        } elseif ($hasAbonnement) {
            $ab = \App\Models\AbonnementAdherent::with('typeAbonnement', 'complexe')->findOrFail($request->abonnement_id);
            $tndAmount = $ab->reste_a_payer ?? $ab->montant_apres_remise ?? $ab->montant_vente ?? 0;
            $contextComplexeId = $ab->complexe_id ?? $ab->typeAbonnement?->complexe_id ?? null;
        }
        

        $stripeCurrency = strtolower(config('services.stripe.currency', 'eur'));

        if ($stripeCurrency === 'tnd') {
            $amountForStripe = (int) round($tndAmount * 1000);
            $display = ['amount' => $amountForStripe, 'currency' => 'tnd'];
        } else {
            $rate = (float) config('services.fx.tnd_to_eur', 0.32);
            $amountEur = round($tndAmount * $rate, 2);
            $amountForStripe = (int) round($amountEur * 100);
            $display = ['amount' => $amountForStripe, 'currency' => $stripeCurrency, 'amount_display' => number_format($amountEur, 2, '.', '')];
        }

        return response()->json(['success' => true, 'data' => $display]);
    }

    public function mesCommandes(): JsonResponse
    {
        $user = auth('api')->user();

        $commandes = Commande::with(['complexe', 'lignes.produit', 'reglements'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $commandes->each(function ($commande) {
            $commande->lignes->each(function ($ligne) {
                $ligne->produit_nom = $ligne->produit->nom;
            });
        });

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    public function show(Commande $commande): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        if ($commande->user_id !== $user->id && ! $user->isAdmin() && ! $user->isGerant()) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        $commande->load(['complexe', 'lignes.produit', 'reglements']);

        $commande->lignes->each(function ($ligne) {
            $ligne->produit_nom = $ligne->produit->nom;
        });

        return response()->json([
            'success' => true,
            'data' => $commande,
        ]);
    }

    protected function createStripeRefund(string $paymentIntentId, Commande $commande): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $refund = Refund::create([
            'payment_intent' => $paymentIntentId,
            'metadata' => [
                'commande_id' => $commande->id,
                'type' => 'commande',
            ],
        ]);

        return [
            'id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    public function annuler(Commande $commande): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user || $commande->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les commandes en attente peuvent être annulées.',
            ], 422);
        }

        return DB::transaction(function () use ($commande): JsonResponse {
            foreach ($commande->lignes as $ligne) {
                $stock = Stock::where('produit_id', $ligne->produit_id)->first();
                if ($stock) {
                    $stock->increment('quantite_disponible', $ligne->quantite);
                }
            }

            $refundStatus = 'not_requested';
            if ($commande->statut_paiement === 'paye' && $commande->modalite_paiement === 'carte') {
                $refundStatus = 'pending';
            }

            $commande->update([
                'statut' => 'annulee',
                'refund_status' => $refundStatus,
                'refund_reference' => null,
            ]);

            $commande->load(['user', 'complexe.owner']);

            if ($commande->user) {
                $commande->user->notify(new \App\Notifications\OrderStatusChanged($commande));
            }

            if ($commande->complexe?->owner && $refundStatus === 'pending') {
                $commande->complexe->owner->notify(new \App\Notifications\OrderStatusChanged(
                    $commande,
                    "La commande #{$commande->id} a été annulée et nécessite un remboursement manuel pour le complexe."
                ));
            }

            return response()->json([
                'success' => true,
                'message' => $refundStatus === 'pending'
                    ? 'Commande annulée. Un remboursement sera traité par le gérant.'
                    : 'Commande annulée.',
            ]);
        });
    }

    public function confirmerRemboursement(Commande $commande): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        if (! $user->isAdmin() && ! ($user->isGerant() && $commande->complexe && $commande->complexe->owner_id === $user->id)) {
            return response()->json(['success' => false, 'message' => self::ACCESS_DENIED_MESSAGE], 403);
        }

        if ($commande->refund_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Aucun remboursement en attente.'], 422);
        }

        return DB::transaction(function () use ($commande, $user): JsonResponse {
            if (! $commande->stripe_payment_intent_id) {
                // Manual refund: record refund succeeded and reference, but preserve original payment status
                $commande->update([
                    'refund_status' => 'succeeded',
                    'refund_reference' => 'manual',
                ]);

                AuditService::refund($user, 'Commande', $commande->id, ['status' => 'succeeded', 'method' => 'manual']);

                return response()->json(['success' => true, 'message' => 'Remboursement validé.']);
            }

            try {
                $refundResult = $this->createStripeRefund($commande->stripe_payment_intent_id, $commande);
                $refundStatus = $refundResult['status'] === 'succeeded' ? 'succeeded' : 'failed';
                $refundReference = $refundResult['id'] ?? null;

                // Preserve original payment status; record refund result and reference
                $commande->update([
                    'refund_status' => $refundStatus,
                    'refund_reference' => $refundReference,
                ]);

                AuditService::refund($user, 'Commande', $commande->id, ['status' => $refundStatus, 'reference' => $refundReference, 'method' => 'stripe']);
            } catch (\Throwable $e) {
                $commande->update([
                    'refund_status' => 'failed',
                    'refund_reference' => null,
                ]);

                AuditService::refund($user, 'Commande', $commande->id, ['status' => 'failed', 'error' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'message' => 'Remboursement validé.']);
        });
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Commande::with(['user:id,first_name,last_name,email', 'lignes.produit'])
            ->whereIn('complexe_id', $myComplexeIds);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('statut_paiement')) {
            $query->where('statut_paiement', $request->statut_paiement);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $commandes = $query->orderByDesc('created_at')->get();

        $commandes->each(function ($commande) {
            $commande->client_nom = $commande->user->first_name.' '.$commande->user->last_name;
            $commande->client_email = $commande->user->email;
            $commande->produits = $commande->lignes->map(function ($ligne) {
                return [
                    'id' => $ligne->produit->id,
                    'nom' => $ligne->produit->nom,
                    'quantite' => $ligne->quantite,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $commandes,
        ]);
    }

    public function updateStatut(Request $request, Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:en_attente,confirmee,preparee,livree,annulee',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $newStatut = $request->statut;
        $oldStatut = $commande->statut;

        return DB::transaction(function () use ($commande, $newStatut, $oldStatut) {
            // Replenish stock only when transitioning INTO annulee from a non-cancelled state
            if ($newStatut === 'annulee' && $oldStatut !== 'annulee') {
                $commande->load('lignes');
                foreach ($commande->lignes as $ligne) {
                    $stock = Stock::where('produit_id', $ligne->produit_id)->lockForUpdate()->first();
                    if ($stock) {
                        $stock->increment('quantite_disponible', $ligne->quantite);
                    }
                }
            }

            // Re-decrement if transitioning OUT of annulee back to an active state
            if ($oldStatut === 'annulee' && $newStatut !== 'annulee') {
                $commande->load('lignes');
                foreach ($commande->lignes as $ligne) {
                    $stock = Stock::where('produit_id', $ligne->produit_id)->lockForUpdate()->first();
                    if ($stock) {
                        $stock->decrement('quantite_disponible', $ligne->quantite);
                    }
                }
            }

            $commande->update(['statut' => $newStatut]);

            $commande->load(['user', 'complexe.owner']);

            // Notify client of status change
            if ($commande->user) {
                $commande->user->notify(new \App\Notifications\OrderStatusChanged($commande));
            }

            // If marked delivered but unpaid, notify complex owner (gérant)
            if ($newStatut === 'livree' && $commande->statut_paiement === 'non_paye') {
                $owner = $commande->complexe?->owner;
                if ($owner) {
                    $owner->notify(new \App\Notifications\OrderStatusChanged(
                        $commande,
                        "Alerte : La commande #{$commande->id} a été livrée mais n'est pas encore payée. N'oubliez pas de confirmer le règlement."
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $commande->fresh(),
            ]);
        });
    }

    public function confirmerPaiement(Request $request, Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        if ($commande->statut === 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de payer une commande annulée.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            'reference' => ['required_if:modalite_paiement,carte', 'nullable', 'string', 'max:100', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
            'montant' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();

        return DB::transaction(function () use ($commande, $request, $user) {
            $montant = (float) ($request->input('montant') ?? ($commande->montant_total - ($commande->montant_paye ?? 0)));
            if ($montant <= 0) {
                return response()->json(['success' => false, 'message' => 'Montant invalide pour le règlement.'], 422);
            }

            $commande->refresh();

            // Record reglement
            \App\Models\ReglementCommande::create([
                'commande_id' => $commande->id,
                'type' => 'paiement',
                'montant' => $montant,
                'reference' => $request->input('reference') ?? null,
            ]);

            $nouveauMontantPaye = (float) (($commande->montant_paye ?? 0) + $montant);

            $statut = $nouveauMontantPaye >= (float) $commande->montant_total ? 'paye' : 'partiel';

            $commande->update([
                'statut_paiement' => $statut,
                'modalite_paiement' => $request->modalite_paiement,
                'montant_paye' => $nouveauMontantPaye,
                'reference_paiement' => $request->input('reference') ?? $commande->reference_paiement,
            ]);

            AuditService::payment($user, 'Commande', $commande->id, $montant, $request->modalite_paiement);

            $commande->load('user');
            if ($commande->user) {
                $commande->user->notify(new \App\Notifications\OrderStatusChanged(
                    $commande,
                    "Le paiement de votre commande #{$commande->id} a été enregistré. Montant: {$montant} TND."
                ));
            }

            return response()->json([
                'success' => true,
                'data' => $commande->fresh()->load('reglements'),
            ]);
        });
    }

    public function adminAnnuler(Commande $commande): JsonResponse
    {
        $this->authorizeGerant($commande->complexe);

        $user = auth('api')->user();

        return DB::transaction(function () use ($commande, $user) {
            foreach ($commande->lignes as $ligne) {
                $stock = Stock::where('produit_id', $ligne->produit_id)->first();
                if ($stock) {
                    $stock->increment('quantite_disponible', $ligne->quantite);
                }
            }

            $commande->update(['statut' => 'annulee']);

            AuditService::cancel($user, 'Commande', $commande->id, 'Admin cancelled commande');

            return response()->json(['success' => true, 'message' => 'Commande annulée.']);
        });
    }
}
