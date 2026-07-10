<?php

namespace App\Http\Controllers;

use App\Models\BonSortie;
use App\Models\Complexe;
use App\Models\LigneBonSortie;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\VenteDirecte;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VenteDirecteController extends Controller
{
    private function authorizeGerant(Complexe $complexe): void
    {
        $user = auth('api')->user();
        if ($user->role === 'gerant' && $complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
        }
    }

    private function generateBonSortieRef(int $complexeId): string
    {
        $year   = date('Y');
        $prefix = "BS-{$year}-";
        $last   = BonSortie::where('reference', 'like', $prefix . '%')
            ->where('complexe_id', $complexeId)
            ->lockForUpdate()
            ->orderByDesc('reference')
            ->first();

        $maxSeq = 0;
        if ($last) {
            $parts  = explode('-', $last->reference);
            $maxSeq = (int) end($parts);
        }

        return $prefix . str_pad($maxSeq + 1, 4, '0', STR_PAD_LEFT);
    }


    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $ventes = VenteDirecte::with(['produit', 'complexe', 'user'])
            ->whereIn('complexe_id', $myComplexeIds)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ventes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Support both flat format (legacy) and array format (frontend)
        $hasLignes = $request->has('lignes') && is_array($request->lignes);

        if ($hasLignes) {
            // Array format: { complexe_id, client_nom, modalite_paiement, lignes: [{ produit_id, quantite }, ...] }
            $validator = Validator::make($request->all(), [
                'complexe_id' => 'required|exists:complexes,id',
                'modalite_paiement' => 'required|in:especes',
                'client_nom' => 'nullable|string|max:255',
                'user_id' => 'nullable|exists:users,id',
                'notes' => 'nullable|string',
                'stripe_payment_intent_id' => 'nullable|string|max:255',
                'lignes' => 'required|array|min:1',
                'lignes.*.produit_id' => 'required|exists:produits,id',
                'lignes.*.quantite' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $complexe = Complexe::findOrFail($request->complexe_id);
            $this->authorizeGerant($complexe);

            try {
                return DB::transaction(function () use ($request, $complexe) {
                    $ventes = [];
                    $year = date('Y');
                    $prefix = "TXN-{$year}-";

                    // Get the max existing sequence number for this year with row locking to avoid duplicate references
                    $existing = DB::table('vente_directes')
                        ->where('reference', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->pluck('reference');

                    $maxSeq = 0;
                    foreach ($existing as $ref) {
                        $parts = explode('-', $ref);
                        $seq = intval(end($parts));
                        if ($seq > $maxSeq) {
                            $maxSeq = $seq;
                        }
                    }
                    $nextSeq = $maxSeq + 1;

                    foreach ($request->lignes as $ligne) {
                        $produit = Produit::findOrFail($ligne['produit_id']);

                        $stock = Stock::where('produit_id', $ligne['produit_id'])->lockForUpdate()->first();
                        if (! $stock || $stock->quantite_disponible < $ligne['quantite']) {
                            throw new \Exception('Stock insuffisant pour ' . $produit->nom);
                        }

                        $prixUnitaire = $produit->prix;
                        $montantTotal = $prixUnitaire * $ligne['quantite'];
                        $reference = $prefix . str_pad($nextSeq++, 5, '0', STR_PAD_LEFT);

                        $vente = VenteDirecte::create([
                            'produit_id' => $produit->id,
                            'complexe_id' => $complexe->id,
                            'reference' => $reference,
                            'quantite' => $ligne['quantite'],
                            'prix_unitaire' => $prixUnitaire,
                            'montant_total' => $montantTotal,
                            'modalite_paiement' => $request->modalite_paiement,
                            'stripe_payment_intent_id' => $request->stripe_payment_intent_id,
                            'client_nom' => $request->client_nom,
                            'user_id' => $request->user_id,
                            'notes' => $request->notes,
                        ]);

                        $stock->decrement('quantite_disponible', $ligne['quantite']);
                        $ventes[] = $vente;
                    }

                    if (! empty($ventes)) {
                        AuditService::payment(
                            auth('api')->user(),
                            'VenteDirecte',
                            $ventes[0]->id,
                            collect($ventes)->sum('montant_total'),
                            $request->modalite_paiement
                        );
                    }

                    // Auto-create a BonSortie for the stock audit trail
                    $bonRef = $this->generateBonSortieRef($complexe->id);
                    $bon = BonSortie::create([
                        'reference'         => $bonRef,
                        'date_bon_sor'      => now()->toDateString(),
                        'total_ttc_bon_sor' => collect($ventes)->sum('montant_total'),
                        'complexe_id'       => $complexe->id,
                        'motif'             => 'Vente directe — ' . ($request->client_nom ?? 'client anonyme'),
                        'created_by'        => auth('api')->id(),
                    ]);
                    foreach ($ventes as $v) {
                        LigneBonSortie::create([
                            'bon_sortie_id'                 => $bon->id,
                            'produit_id'                    => $v->produit_id,
                            'quantite_entree_lig_bon_sor'   => $v->quantite,
                            'prix_unitaire_constate'        => $v->prix_unitaire,
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'data' => count($ventes) === 1 ? $ventes[0] : $ventes,
                        'count' => count($ventes),
                    ], 201);
                });
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        // Legacy flat format: { produit_id, complexe_id, quantite, ... }
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'complexe_id' => 'required|exists:complexes,id',
            'quantite' => 'required|integer|min:1',
            'modalite_paiement' => 'required|in:especes',
            'stripe_payment_intent_id' => 'nullable|string|max:255',
            'client_nom' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            $produit = Produit::with(['stock', 'complexe'])->findOrFail($request->produit_id);
            $complexeId = $request->complexe_id;

            $complexe = Complexe::findOrFail($complexeId);
            $this->authorizeGerant($complexe);

            $stock = $produit->stock;
            if (! $stock || $stock->quantite_disponible < $request->quantite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant pour ' . $produit->nom,
                ], 422);
            }

            $prixUnitaire = $produit->prix;
            $montantTotal = $prixUnitaire * $request->quantite;

            // Generate unique reference TXN-YYYY-NNNNN for this year
            $year = date('Y');
            $prefix = "TXN-{$year}-";
            $existing = DB::table('vente_directes')
                ->where('reference', 'like', $prefix . '%')
                ->lockForUpdate()
                ->pluck('reference');
            $maxSeq = 0;
            foreach ($existing as $ref) {
                $parts = explode('-', $ref);
                $seq = intval(end($parts));
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
            $nextSeq = $maxSeq + 1;
            $reference = $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

            $vente = VenteDirecte::create([
                'produit_id'         => $produit->id,
                'complexe_id'        => $complexeId,
                'reference'          => $reference,
                'quantite'           => $request->quantite,
                'prix_unitaire'      => $prixUnitaire,
                'montant_total'      => $montantTotal,
                'modalite_paiement'  => $request->modalite_paiement,
                'stripe_payment_intent_id' => $request->stripe_payment_intent_id,
                'client_nom'         => $request->client_nom,
                'user_id'            => $request->user_id,
                'notes'              => $request->notes,
            ]);

            $stock->decrement('quantite_disponible', $request->quantite);

            // Auto-create BonSortie for audit trail
            $bonRef = $this->generateBonSortieRef($complexeId);
            $bon = BonSortie::create([
                'reference'         => $bonRef,
                'date_bon_sor'      => now()->toDateString(),
                'total_ttc_bon_sor' => $montantTotal,
                'complexe_id'       => $complexeId,
                'motif'             => 'Vente directe — ' . ($request->client_nom ?? 'client anonyme'),
                'created_by'        => auth('api')->id(),
            ]);
            LigneBonSortie::create([
                'bon_sortie_id'               => $bon->id,
                'produit_id'                  => $produit->id,
                'quantite_entree_lig_bon_sor' => $request->quantite,
                'prix_unitaire_constate'      => $prixUnitaire,
            ]);

            AuditService::payment(
                auth('api')->user(),
                'VenteDirecte',
                $vente->id,
                $montantTotal,
                $request->modalite_paiement
            );

            return response()->json([
                'success'    => true,
                'data'       => $vente->load(['produit', 'complexe']),
                'bon_sortie' => $bon->reference,
            ], 201);
        });
    }
}
