<?php

namespace App\Http\Controllers;

use App\Models\TypeDepense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TypeDepenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TypeDepense::orderBy('designation_ty_dep');

        if ($request->boolean('active_only')) {
            $query->where('active', true);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
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

        $data = $validator->validated();
        if ($request->has('active')) {
            $data['active'] = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
        }

        $typeDepense->update($data);

        return response()->json(['success' => true, 'data' => $typeDepense->fresh()]);
    }

    public function destroy(TypeDepense $typeDepense): JsonResponse
    {
        $count = $typeDepense->depenses()->count();
        if ($count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer : ce type de dépense est utilisé par {$count} dépense(s). Désactivez-le à la place.",
            ], 422);
        }

        $typeDepense->delete();

        return response()->json(['success' => true, 'message' => 'Type de dépense supprimé.']);
    }
}
