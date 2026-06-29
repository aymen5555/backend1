<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Equipement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EquipementController extends Controller
{
    public function index(): JsonResponse
    {
        $equipements = Equipement::orderBy('nom_eq')->get();

        return response()->json(['success' => true, 'data' => $equipements]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_eq' => 'required|string|max:255',
            'icone_eq' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $equipement = Equipement::create($validator->validated());

        return response()->json(['success' => true, 'data' => $equipement], 201);
    }

    public function update(Request $request, Equipement $equipement): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_eq' => 'required|string|max:255',
            'icone_eq' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $equipement->update($validator->validated());

        return response()->json(['success' => true, 'data' => $equipement]);
    }

    public function destroy(Equipement $equipement): JsonResponse
    {
        $equipement->delete();

        return response()->json(['success' => true, 'message' => 'Équipement supprimé.']);
    }

    public function toggleComplexe(Request $request, Equipement $equipement, $complexeId): JsonResponse
    {
        $complexe = Complexe::findOrFail($complexeId);
        $attached = $complexe->equipements()->toggle($equipement->id);

        return response()->json([
            'success' => true,
            'attached' => $attached['attached'],
            'detached' => $attached['detached'],
        ]);
    }

    public function complexeEquipements($complexeId): JsonResponse
    {
        $complexe = Complexe::with('equipements')->findOrFail($complexeId);

        return response()->json(['success' => true, 'data' => $complexe->equipements]);
    }
}
