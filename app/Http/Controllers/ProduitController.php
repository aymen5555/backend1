<?php

namespace App\Http\Controllers;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProduitController extends Controller
{
    /* -----------------------------------------------------
     | PUBLIC - no auth required
     ----------------------------------------------------- */

    /** GET /api/produits */
    public function index(Request $request): JsonResponse
    {
        $query = Produit::with(['categorie', 'stock', 'complexe' => function ($q) {
            $q->select('id', 'name', 'city');
        }])
            ->where('actif', true)
            ->whereHas('stock', function ($q) {
                $q->where('quantite_disponible', '>', 0);
            });

        if ($request->filled('complexe_id')) {
            $query->where('complexe_id', $request->complexe_id);
        }

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->filled('sport_cible')) {
            $query->where('sport_cible', $request->sport_cible);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get(),
        ]);
    }

    /** GET /api/produits/{id} */
    public function show(Produit $produit): JsonResponse
    {
        if (!$produit->actif) {
            return response()->json(['success' => false, 'message' => 'Produit introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $produit->load(['categorie', 'stock', 'complexe']),
        ]);
    }

    /* -----------------------------------------------------
     | ADMIN - GERANT + SUPER_ADMIN
     ----------------------------------------------------- */

    private function authorizeGerant(Complexe $complexe): void
    {
        $user = auth('api')->user();
        if ($user->role === 'gerant' && $complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
        }
    }

    /** GET /api/admin/produits */
    public function adminIndex(): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Produit::with(['categorie', 'stock', 'complexe'])
            ->whereIn('complexe_id', $myComplexeIds);

        $produits = $query->latest()->get();

        // Add low-stock alert flag
        $produits->each(function ($produit) {
            $produit->alerte_stock = $produit->stock && $produit->stock->alerteStock();
        });

        return response()->json(['success' => true, 'data' => $produits]);
    }

    /** POST /api/admin/produits */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categorie_produits,id',
            'complexe_id' => 'required|exists:complexes,id',
            'prix' => 'required|numeric|min:0',
            'sport_cible' => 'required|in:football,padel,tennis,natation,musculation,yoga,fitness,basketball,volleyball,handball,general',
            'niveau_cible' => 'required|in:debutant,intermediaire,expert,tous',
            'description' => 'nullable|string',
            'image' => 'nullable|url|max:1000',
            'reference' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^PRD-[A-Z0-9]{2,5}-[A-Z0-9]{3,8}$/i',
                'unique:produits,reference',
            ],
            'quantite_initiale' => 'required|integer|min:0',
            'quantite_minimale' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $complexe = Complexe::findOrFail($request->complexe_id);
        $this->authorizeGerant($complexe);

        $produit = Produit::create($validator->validated());

        // Create stock record
        Stock::create([
            'produit_id' => $produit->id,
            'quantite_disponible' => $request->quantite_initiale,
            'quantite_minimale' => $request->quantite_minimale ?? 5,
        ]);

        return response()->json([
            'success' => true,
            'data' => $produit->load(['categorie', 'stock', 'complexe']),
        ], 201);
    }

    /** PUT /api/admin/produits/{id} */
    public function update(Request $request, Produit $produit): JsonResponse
    {
        $this->authorizeGerant($produit->complexe);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'categorie_id' => 'sometimes|exists:categorie_produits,id',
            'complexe_id' => 'sometimes|exists:complexes,id',
            'prix' => 'sometimes|numeric|min:0',
            'sport_cible' => 'sometimes|in:football,padel,tennis,natation,musculation,yoga,fitness,basketball,volleyball,handball,general',
            'niveau_cible' => 'sometimes|in:debutant,intermediaire,expert,tous',
            'description' => 'nullable|string',
            'image' => 'nullable|url|max:1000',
            'reference' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^PRD-[A-Z0-9]{2,5}-[A-Z0-9]{3,8}$/i',
                "unique:produits,reference,{$produit->id}",
            ],
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // If the request includes a new complexe_id, ensure the acting user
        // (when a gerant) is authorized for that complexe as well. This prevents
        // a gerant from changing a produit to belong to another complexe.
        if ($request->filled('complexe_id')) {
            $newComplexe = Complexe::findOrFail($request->complexe_id);
            $this->authorizeGerant($newComplexe);
        }

        $produit->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $produit->fresh()->load(['categorie', 'stock', 'complexe']),
        ]);
    }

    /** DELETE /api/admin/produits/{id} */
    public function destroy(Produit $produit): JsonResponse
    {
        $this->authorizeGerant($produit->complexe);

        $produit->update(['actif' => false]);

        return response()->json(['success' => true, 'message' => 'Produit désactivé.']);
    }

    /** PUT /api/admin/produits/{id}/stock */
    public function updateStock(Request $request, Produit $produit): JsonResponse
    {
        $this->authorizeGerant($produit->complexe);

        $validator = Validator::make($request->all(), [
            'quantite_disponible' => 'required|integer|min:0',
            'quantite_minimale' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $stock = $produit->stock;
        if ($stock) {
            $stock->update($validator->validated());
        } else {
            $stock = Stock::create([
                'produit_id' => $produit->id,
                'quantite_disponible' => $request->quantite_disponible,
                'quantite_minimale' => $request->quantite_minimale ?? 5,
            ]);
        }

        return response()->json(['success' => true, 'data' => $stock->fresh()]);
    }
}