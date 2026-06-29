<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Fournisseur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FournisseurController extends Controller
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

        $fournisseurs = Fournisseur::whereIn('complexe_id', $myComplexeIds)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fournisseurs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'nom' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $complexe = Complexe::findOrFail($request->complexe_id);
        $this->authorizeGerant($complexe);

        $fournisseur = Fournisseur::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $fournisseur,
        ], 201);
    }

    public function update(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        $this->authorizeGerant($fournisseur->complexe);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'contact' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'adresse' => 'nullable|string',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fournisseur->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $fournisseur->fresh(),
        ]);
    }

    public function destroy(Fournisseur $fournisseur): JsonResponse
    {
        $this->authorizeGerant($fournisseur->complexe);

        $fournisseur->update(['actif' => false]);

        return response()->json(['success' => true, 'message' => 'Fournisseur désactivé.']);
    }
}
