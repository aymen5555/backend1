<?php

namespace App\Http\Controllers;

use App\Models\BonSortie;
use App\Models\Complexe;
use App\Models\LigneBonSortie;
use App\Models\Produit;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BonSortieController extends Controller
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
        $last = BonSortie::where('reference', 'like', $prefix . $year . '-%')
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

        return $prefix . $year . '-' . str_pad($maxSeq + 1, 4, '0', STR_PAD_LEFT);
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = BonSortie::with(['complexe', 'lignes.produit', 'creePar'])
            ->whereIn('complexe_id', $myComplexeIds)
            ->orderByDesc('date_bon_sor');

        if ($request->has('complexe_id')) {
            $query->where('complexe_id', $request->query('complexe_id'));
        }

        if ($request->has('date_debut')) {
            $query->where('date_bon_sor', '>=', $request->query('date_debut'));
        }

        if ($request->has('date_fin')) {
            $query->where('date_bon_sor', '<=', $request->query('date_fin'));
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
            'complexe_id' => 'required|exists:complexes,id',
            'date_bon_sor' => 'required|date|before_or_equal:today',
            'motif' => 'nullable|string|max:255',
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $this->authorizeGerant($validated['complexe_id']);

        $complexeId = $validated['complexe_id'];
        $produitsInsuffisants = [];

        foreach ($validated['lignes'] as $ligne) {
            $produit = Produit::with('stock')->findOrFail($ligne['produit_id']);
            if ((int) $produit->complexe_id !== (int) $complexeId) {
                return response()->json([
                    'success' => false,
                    'message' => "Le produit '{$produit->nom}' n'appartient pas à ce complexe.",
                ], 422);
            }

            $stock = $produit->stock;
            $disponible = $stock ? $stock->quantite_disponible : 0;

            if ($disponible < $ligne['quantite']) {
                $produitsInsuffisants[] = "Stock insuffisant pour '{$produit->nom}' : demandé {$ligne['quantite']}, disponible {$disponible}";
            }
        }

        if (! empty($produitsInsuffisants)) {
            return response()->json([
                'success' => false,
                'message' => implode("\n", $produitsInsuffisants),
            ], 422);
        }

        return DB::transaction(function () use ($validated, $complexeId) {
            $reference = $this->generateReference($complexeId, 'BS-');

            $bon = BonSortie::create([
                'reference' => $reference,
                'date_bon_sor' => $validated['date_bon_sor'],
                'total_ttc_bon_sor' => 0,
                'complexe_id' => $complexeId,
                'motif' => $validated['motif'] ?? null,
                'created_by' => auth('api')->id(),
            ]);

            $total = 0;

            foreach ($validated['lignes'] as $ligne) {
                $produit = Produit::with('stock')->findOrFail($ligne['produit_id']);
                $stock = $produit->stock;
                $prixUnitaire = $stock ? (float) ($produit->prix_achat ?? 0) : 0;

                $sousTotal = $prixUnitaire * $ligne['quantite'];

                LigneBonSortie::create([
                    'bon_sortie_id' => $bon->id,
                    'produit_id' => $produit->id,
                    'quantite_entree_lig_bon_sor' => $ligne['quantite'],
                    'prix_unitaire_constate' => $prixUnitaire,
                ]);

                if ($stock) {
                    $stock->decrement('quantite_disponible', $ligne['quantite']);
                }

                $total += $sousTotal;
            }

            $bon->update(['total_ttc_bon_sor' => $total]);

            return response()->json([
                'success' => true,
                'data' => $bon->load(['complexe', 'lignes.produit']),
                'reference' => $reference,
            ], 201);
        });
    }

    public function show(BonSortie $bonSortie): JsonResponse
    {
        $this->authorizeGerant($bonSortie->complexe_id);
        $bonSortie->load(['complexe', 'lignes.produit', 'creePar', 'reglements']);

        return response()->json(['success' => true, 'data' => $bonSortie]);
    }

    public function confirmerPaiement(Request $request, BonSortie $bonSortie): JsonResponse
    {
        $this->authorizeGerant($bonSortie->complexe_id);

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

        return DB::transaction(function () use ($bonSortie, $data, $user) {
            $montant = (float) $data['montant'];

            $reg = \App\Models\ReglementBonSortie::create([
                'bon_sortie_id' => $bonSortie->id,
                'type' => $data['type'] ?? 'paiement',
                'montant' => $montant,
                'reference' => $data['reference'] ?? null,
                'created_by' => auth('api')->id(),
            ]);

            $nouveauPaye = (float) $bonSortie->montant_paye + $montant;
            $statut = $nouveauPaye >= (float) $bonSortie->total_ttc_bon_sor ? 'paye' : 'partiel';

            $bonSortie->update([
                'montant_paye' => $nouveauPaye,
                'reference_paiement' => $data['reference'] ?? $bonSortie->reference_paiement,
                'statut_paiement' => $statut,
            ]);

            AuditService::payment($user, 'BonSortie', $bonSortie->id, $montant, $data['type'] ?? 'paiement');

            return response()->json(['success' => true, 'data' => $reg, 'bon' => $bonSortie->fresh()]);
        });
    }
}
