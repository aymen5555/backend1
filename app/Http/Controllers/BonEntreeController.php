<?php

namespace App\Http\Controllers;

use App\Models\BonEntree;
use App\Models\Complexe;
use App\Models\LigneBonEntree;
use App\Models\Produit;
use App\Models\Stock;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BonEntreeController extends Controller
{
    private function authorizeGerant(int $complexeId): void
    {
        $user = auth('api')->user();
        if ($user && $user->role === 'gerant') {
            $hasAccess = Complexe::where('id', $complexeId)
                ->where('owner_id', $user->id)
                ->exists();
            if (! $hasAccess) {
                abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
            }
        }
    }

    private function generateReference(int $complexeId, string $prefix): string
    {
        $year = date('Y');
        $last = BonEntree::where('reference', 'like', $prefix.$year.'-%')
            ->where('complexe_id', $complexeId)
            ->lockForUpdate()
            ->orderByDesc('reference')
            ->first();

        $maxSeq = 0;
        if ($last) {
            $parts = explode('-', $last->reference);
            $seq = intval(end($parts));
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        return $prefix.$year.'-'.str_pad($maxSeq + 1, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = BonEntree::with(['fournisseurInterne', 'complexe', 'lignes.produit', 'creePar'])
            ->whereIn('complexe_id', $myComplexeIds)
            ->orderByDesc('date_bon_ent');

        if ($request->has('complexe_id')) {
            $query->where('complexe_id', $request->query('complexe_id'));
        }

        if ($request->has('date_debut')) {
            $query->where('date_bon_ent', '>=', $request->query('date_debut'));
        }

        if ($request->has('date_fin')) {
            $query->where('date_bon_ent', '<=', $request->query('date_fin'));
        }

        $bons = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $bons->items(),
            'meta' => [
                'total' => $bons->total(),
                'per_page' => $bons->perPage(),
                'current_page' => $bons->currentPage(),
                'last_page' => $bons->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fournisseur_interne_id' => 'required|exists:fournisseurs_internes,id',
            'complexe_id' => 'required|exists:complexes,id',
            'date_bon_ent' => 'required|date|before_or_equal:today',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $this->authorizeGerant($validated['complexe_id']);

        $complexeId = $validated['complexe_id'];

        foreach ($validated['lignes'] as $ligne) {
            $produit = Produit::findOrFail($ligne['produit_id']);
            if ((int) $produit->complexe_id !== (int) $complexeId) {
                return response()->json([
                    'success' => false,
                    'message' => "Le produit '{$produit->nom}' n'appartient pas à ce complexe.",
                ], 422);
            }
        }

        return DB::transaction(function () use ($validated, $complexeId) {
            $reference = $this->generateReference($complexeId, 'BE-');

            $bon = BonEntree::create([
                'reference' => $reference,
                'date_bon_ent' => $validated['date_bon_ent'],
                'total_ttc_bon_ent' => 0,
                'fournisseur_interne_id' => $validated['fournisseur_interne_id'],
                'complexe_id' => $complexeId,
                'created_by' => auth('api')->id(),
            ]);

            $total = 0;

            foreach ($validated['lignes'] as $ligne) {
                $produit = Produit::with('stock')->findOrFail($ligne['produit_id']);
                $stock = $produit->stock;

                $sousTotal = (float) $ligne['quantite'] * (float) $ligne['prix_unitaire'];

                LigneBonEntree::create([
                    'bon_entree_id' => $bon->id,
                    'produit_id' => $produit->id,
                    'quantite_entree_lig_bon_ent' => $ligne['quantite'],
                    'prix_unitaire_dachat_lig_bon_ent' => $ligne['prix_unitaire'],
                    'sous_total' => $sousTotal,
                ]);

                if ($stock) {
                    $stock->increment('quantite_disponible', $ligne['quantite']);
                } else {
                    Stock::create([
                        'produit_id' => $produit->id,
                        'quantite_disponible' => $ligne['quantite'],
                        'quantite_minimale' => 5,
                    ]);
                }

                $total += $sousTotal;
            }

            $bon->update(['total_ttc_bon_ent' => $total]);

            return response()->json([
                'success' => true,
                'data' => $bon->load(['fournisseurInterne', 'complexe', 'lignes.produit']),
                'reference' => $reference,
            ], 201);
        });
    }

    public function show(BonEntree $bonEntree): JsonResponse
    {
        $this->authorizeGerant($bonEntree->complexe_id);
        $bonEntree->load(['fournisseurInterne', 'complexe', 'lignes.produit', 'creePar', 'reglements']);

        return response()->json(['success' => true, 'data' => $bonEntree]);
    }

    public function confirmerPaiement(Request $request, BonEntree $bonEntree): JsonResponse
    {
        $this->authorizeGerant($bonEntree->complexe_id);
        
        $validator = Validator::make($request->all(), [
            'montant' => 'required|numeric|min:0.01',
            'type' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $user = auth('api')->user();

        return DB::transaction(function () use ($bonEntree, $data, $user) {
            $montant = (float) $data['montant'];

            $reg = \App\Models\ReglementBonEntree::create([
                'bon_entree_id' => $bonEntree->id,
                'type' => $data['type'] ?? 'paiement',
                'montant' => $montant,
                'reference' => $data['reference'] ?? null,
                'created_by' => auth('api')->id(),
            ]);

            $nouveauPaye = (float) $bonEntree->montant_paye + $montant;
            $statut = $nouveauPaye >= (float) $bonEntree->total_ttc_bon_ent ? 'paye' : 'partiel';

            $bonEntree->update([
                'montant_paye' => $nouveauPaye,
                'reference_paiement' => $data['reference'] ?? $bonEntree->reference_paiement,
                'statut_paiement' => $statut,
            ]);

            AuditService::payment($user, 'BonEntree', $bonEntree->id, $montant, $data['type'] ?? 'paiement');

            return response()->json(['success' => true, 'data' => $reg, 'bon' => $bonEntree->fresh()]);
        });
    }
}
