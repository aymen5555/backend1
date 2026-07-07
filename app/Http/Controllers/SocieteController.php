<?php

namespace App\Http\Controllers;

use App\Models\Societe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocieteController extends Controller
{
    public function index(): JsonResponse
    {
        $societes = Societe::with('complexes')->withCount('complexes')->orderBy('nom_soc')->get();

        return response()->json(['success' => true, 'data' => $societes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_soc' => 'required|string|max:255',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'telephone' => 'nullable|string|max:30',
            'date_de_creation' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $societe = Societe::create($validator->validated());

        return response()->json(['success' => true, 'data' => $societe], 201);
    }

    public function show(Societe $societe): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $societe->load('dirigeants', 'complexes')]);
    }

    public function update(Request $request, Societe $societe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_soc' => 'required|string|max:255',
            'image' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'telephone' => 'nullable|string|max:30',
            'date_de_creation' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $societe->update($validator->validated());

        return response()->json(['success' => true, 'data' => $societe]);
    }

    public function destroy(Societe $societe): JsonResponse
    {
        $societe->delete();

        return response()->json(['success' => true, 'message' => 'Société supprimée.']);
    }
}
