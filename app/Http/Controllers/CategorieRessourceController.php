<?php

namespace App\Http\Controllers;

use App\Models\CategorieRessource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategorieRessourceController extends Controller
{
    public function index(): JsonResponse
    {
        $cats = CategorieRessource::orderBy('nom_cat_res')->get();

        return response()->json(['success' => true, 'data' => $cats]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_res' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cat = CategorieRessource::create($validator->validated());

        return response()->json(['success' => true, 'data' => $cat], 201);
    }

    public function update(Request $request, CategorieRessource $categorieRessource): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_res' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $categorieRessource->update($validator->validated());

        return response()->json(['success' => true, 'data' => $categorieRessource]);
    }

    public function destroy(CategorieRessource $categorieRessource): JsonResponse
    {
        $categorieRessource->delete();

        return response()->json(['success' => true, 'message' => 'Catégorie ressource supprimée.']);
    }
}
