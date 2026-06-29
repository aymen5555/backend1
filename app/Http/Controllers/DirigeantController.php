<?php

namespace App\Http\Controllers;

use App\Models\Dirigeant;
use App\Models\Societe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DirigeantController extends Controller
{
    public function index(Societe $societe): JsonResponse
    {
        $dirigeants = $societe->dirigeants()->orderBy('nom_dir')->get();

        return response()->json(['success' => true, 'data' => $dirigeants]);
    }

    public function store(Request $request, Societe $societe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom_dir' => 'required|string|max:255',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $dirigeant = $societe->dirigeants()->create($validator->validated());

        return response()->json(['success' => true, 'data' => $dirigeant], 201);
    }

    public function destroy(Societe $societe, Dirigeant $dirigeant): JsonResponse
    {
        $dirigeant->delete();

        return response()->json(['success' => true, 'message' => 'Dirigeant supprimé.']);
    }
}
