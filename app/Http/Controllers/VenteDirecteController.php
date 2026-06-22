<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\VenteDirecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class VenteDirecteController extends Controller
{
    private function authorizeGerant(Complexe $complexe): void
    {
        $user = auth('api')->user();
        if ($user->role === 'gerant' && $complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
        }
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
        $validator = Validator::make($request->all(), [
            'produit_id' => 'required|exists:produits,id',
            'complexe_id' => 'required|exists:complexes,id',
            'quantite' => 'required|integer|min:1',
            'modalite_paiement' => 'required|in:especes,carte',
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
            if (!$stock || $stock->quantite_disponible < $request->quantite) {
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
            $existing = VenteDirecte::where('reference', 'like', $prefix . '%')->pluck('reference');
            $maxSeq = 0;
            foreach ($existing as $ref) {
                $parts = explode('-', $ref);
                $seq = intval(end($parts));
                if ($seq > $maxSeq) $maxSeq = $seq;
            }
            $nextSeq = $maxSeq + 1;
            $reference = $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

            $vente = VenteDirecte::create([
                'produit_id' => $produit->id,
                'complexe_id' => $complexeId,
                'reference' => $reference,
                'quantite' => $request->quantite,
                'prix_unitaire' => $prixUnitaire,
                'montant_total' => $montantTotal,
                'modalite_paiement' => $request->modalite_paiement,
                'client_nom' => $request->client_nom,
                'user_id' => $request->user_id,
                'notes' => $request->notes,
            ]);

            $stock->decrement('quantite_disponible', $request->quantite);

            return response()->json([
                'success' => true,
                'data' => $vente->load(['produit', 'complexe']),
            ], 201);
        });
    }
}