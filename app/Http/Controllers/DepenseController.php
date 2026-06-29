<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Depense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Depense::with(['typeDepense', 'complexe', 'creePar']);

        if ($user && $user->role === 'gerant') {
            $myComplexeIds = Complexe::where('owner_id', $user->id)->pluck('id');
            $query->whereIn('complexe_id', $myComplexeIds);
        }

        if ($request->has('complexe_id')) {
            $query->where('complexe_id', $request->query('complexe_id'));
        }
        if ($request->has('type_depense_id')) {
            $query->where('type_depense_id', $request->query('type_depense_id'));
        }
        if ($request->has('date_debut')) {
            $query->where('date_depense', '>=', $request->query('date_debut'));
        }
        if ($request->has('date_fin')) {
            $query->where('date_depense', '<=', $request->query('date_fin'));
        }

        $depenses = $query->orderByDesc('date_depense')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $depenses->items(),
            'meta' => [
                'total' => $depenses->total(),
                'per_page' => $depenses->perPage(),
                'current_page' => $depenses->currentPage(),
                'last_page' => $depenses->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_depense' => 'required|date|before_or_equal:today',
            'montant_dep' => 'required|numeric|min:0',
            'commentaire_dep' => 'nullable|string',
            'type_depense_id' => 'required|exists:type_depenses,id',
            'complexe_id' => 'required|exists:complexes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = auth('api')->id();

        $depense = Depense::create($validated);

        return response()->json(['success' => true, 'data' => $depense->load(['typeDepense', 'complexe'])], 201);
    }

    public function update(Request $request, Depense $depense): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_depense' => 'sometimes|date|before_or_equal:today',
            'montant_dep' => 'sometimes|numeric|min:0',
            'commentaire_dep' => 'nullable|string',
            'type_depense_id' => 'sometimes|exists:type_depenses,id',
            'complexe_id' => 'sometimes|exists:complexes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $depense->update($validator->validated());

        return response()->json(['success' => true, 'data' => $depense->load(['typeDepense', 'complexe'])]);
    }

    public function destroy(Depense $depense): JsonResponse
    {
        $depense->delete();

        return response()->json(['success' => true, 'message' => 'Dépense supprimée.']);
    }
}
