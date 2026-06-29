<?php

namespace App\Http\Controllers;

use App\Models\ProfilFitness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfilFitnessController extends Controller
{
    /** GET /api/profile-fitness — get current user's profile (returns null if not created yet) */
    public function show(): JsonResponse
    {
        $user = JWTAuth::user();
        $profil = ProfilFitness::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => $profil, // null if not exists
        ]);
    }

    /** POST /api/profile-fitness — create profile (calculate IMC server-side) */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::user();

        if (ProfilFitness::where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Profile already exists. Use PUT to update.',
            ], 422);
        }

        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Calculate IMC server-side from taille and poids
        $data['imc'] = ProfilFitness::calculerImc(
            (int) $data['taille'],
            (float) $data['poids']
        );
        $data['user_id'] = $user->id;

        $profil = ProfilFitness::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil fitness créé avec succès.',
            'data' => $profil->fresh(),
        ], 201);
    }

    /** PUT /api/profile-fitness — update profile (recalculate IMC) */
    public function update(Request $request): JsonResponse
    {
        $user = JWTAuth::user();
        $profil = ProfilFitness::where('user_id', $user->id)->first();

        if (! $profil) {
            return response()->json([
                'success' => false,
                'message' => 'No fitness profile found. Use POST to create one.',
            ], 404);
        }

        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Recalculate IMC from taille and poids
        $data['imc'] = ProfilFitness::calculerImc(
            (int) $data['taille'],
            (float) $data['poids']
        );

        $profil->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil fitness mis à jour avec succès.',
            'data' => $profil->fresh(),
        ]);
    }

    private function validateRequest(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'taille' => 'required|integer|min:100|max:250',
            'poids' => 'required|numeric|min:30|max:300',
            'objectif_sportif' => 'required|string|in:perte_poids,prise_masse,performance',
            'niveau_sportif' => 'required|string|in:debutant,intermediaire,expert',
            'sport_prefere' => 'required|string|in:football,padel,natation,tennis,musculation,yoga,fitness,volleyball,basketball,handball',
            'poids_cible' => 'nullable|numeric|min:20|max:300',
            'budget_mensuel_min' => 'nullable|numeric|min:0',
            'budget_mensuel_max' => 'nullable|numeric|min:0',
        ]);
    }
}
