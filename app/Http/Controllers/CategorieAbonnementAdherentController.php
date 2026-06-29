<?php

namespace App\Http\Controllers;

use App\Models\CategorieAbonnementAdherent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategorieAbonnementAdherentController extends Controller
{
    public function index(): JsonResponse
    {
        $cats = CategorieAbonnementAdherent::orderBy('nom_cat_abo_ad')->get();

        return response()->json(['success' => true, 'data' => $cats]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_abo_ad' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cat = CategorieAbonnementAdherent::create($validator->validated());

        return response()->json(['success' => true, 'data' => $cat], 201);
    }

    public function update(Request $request, CategorieAbonnementAdherent $categorieAbonnementAdherent): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_cat_abo_ad' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $categorieAbonnementAdherent->update($validator->validated());

        return response()->json(['success' => true, 'data' => $categorieAbonnementAdherent]);
    }

    public function destroy(CategorieAbonnementAdherent $categorieAbonnementAdherent): JsonResponse
    {
        $categorieAbonnementAdherent->delete();

        return response()->json(['success' => true, 'message' => 'Catégorie abonnement supprimée.']);
    }
}
