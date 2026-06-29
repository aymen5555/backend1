<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Galerie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GalerieController extends Controller
{
    private function authorizeGerant(int $complexeId): void
    {
        $user = auth('api')->user();
        if ($user && $user->role === 'gerant') {
            $hasAccess = Complexe::where('id', $complexeId)
                ->where('owner_id', $user->id)
                ->exists();
            if (! $hasAccess) {
                abort(response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403));
            }
        }
    }

    public function index(Request $request, $complexeId): JsonResponse
    {
        $complexe = Complexe::findOrFail($complexeId);
        $this->authorizeGerant($complexe->id);

        $galeries = Galerie::where('complexe_id', $complexeId)
            ->orderBy('ordre')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $galeries]);
    }

    public function store(Request $request, $complexeId): JsonResponse
    {
        $complexe = Complexe::findOrFail($complexeId);
        $this->authorizeGerant($complexe->id);

        $validator = Validator::make($request->all(), [
            'image_g' => 'required|string|max:500',
            'imageKit_file_id_g' => 'nullable|string|max:255',
            'ordre' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['complexe_id'] = $complexe->id;

        $maxOrdre = Galerie::where('complexe_id', $complexeId)->max('ordre') ?? -1;
        $data['ordre'] = $data['ordre'] ?? ($maxOrdre + 1);

        $galerie = Galerie::create($data);

        return response()->json(['success' => true, 'data' => $galerie], 201);
    }

    public function destroy($complexeId, $galerieId): JsonResponse
    {
        $complexe = Complexe::findOrFail($complexeId);
        $this->authorizeGerant($complexe->id);

        $galerie = Galerie::where('complexe_id', $complexeId)->findOrFail($galerieId);
        $galerie->delete();

        return response()->json(['success' => true, 'message' => 'Image supprimée.']);
    }
}
