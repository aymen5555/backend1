<?php

namespace App\Http\Controllers;

use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategorieProduitController extends Controller
{
    /* -----------------------------------------------------
     | PUBLIC - no auth required
     ----------------------------------------------------- */

    /** GET /api/categories-produits */
    public function index(): JsonResponse
    {
        $categories = CategorieProduit::withCount(['produitsActive as produits_count'])
            ->where('active', true)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /* -----------------------------------------------------
     | ADMIN - SUPER_ADMIN only
     ----------------------------------------------------- */

    /** POST /api/admin/categories-produits */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:categorie_produits,nom',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['nom']);

        $categorie = CategorieProduit::create($data);

        return response()->json([
            'success' => true,
            'data' => $categorie,
        ], 201);
    }

    /** PUT /api/admin/categories-produits/{id} */
    public function update(Request $request, CategorieProduit $categorie): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255|unique:categorie_produits,nom,' . $categorie->id,
            'description' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['nom'])) {
            $data['slug'] = Str::slug($data['nom']);
        }

        $categorie->update($data);

        return response()->json([
            'success' => true,
            'data' => $categorie->fresh(),
        ]);
    }

    /** DELETE /api/admin/categories-produits/{id} */
    public function destroy(CategorieProduit $categorie): JsonResponse
    {
        $categorie->update(['active' => false]);

        return response()->json(['success' => true, 'message' => 'Catégorie désactivée.']);
    }
}