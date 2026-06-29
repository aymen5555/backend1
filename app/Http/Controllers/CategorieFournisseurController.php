<?php

namespace App\Http\Controllers;

use App\Models\CategorieFournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategorieFournisseurController extends Controller
{
    public function index(): JsonResponse
    {
        $cats = CategorieFournisseur::orderBy('nom_cat_four')->get();

        return response()->json(['success' => true, 'data' => $cats]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_four' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cat = CategorieFournisseur::create($validator->validated());

        return response()->json(['success' => true, 'data' => $cat], 201);
    }

    public function update(Request $request, CategorieFournisseur $categorieFournisseur): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_four' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $categorieFournisseur->update($validator->validated());

        return response()->json(['success' => true, 'data' => $categorieFournisseur]);
    }

    public function destroy(CategorieFournisseur $categorieFournisseur): JsonResponse
    {
        $categorieFournisseur->delete();

        return response()->json(['success' => true, 'message' => 'Catégorie fournisseur supprimée.']);
    }
}
