<?php

namespace App\Http\Controllers;

use App\Models\TypeDepense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeDepenseController extends Controller
{
    public function index(): JsonResponse
    {
        $types = TypeDepense::orderBy('designation_ty_dep')->get();

        return response()->json(['success' => true, 'data' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'designation_ty_dep' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $type = TypeDepense::create($validator->validated());

        return response()->json(['success' => true, 'data' => $type], 201);
    }

    public function update(Request $request, TypeDepense $typeDepense): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'designation_ty_dep' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $typeDepense->update($validator->validated());

        return response()->json(['success' => true, 'data' => $typeDepense]);
    }

    public function destroy(TypeDepense $typeDepense): JsonResponse
    {
        $typeDepense->delete();

        return response()->json(['success' => true, 'message' => 'Type de dépense supprimé.']);
    }
}
