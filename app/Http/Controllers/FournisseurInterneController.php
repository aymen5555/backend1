<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\FournisseurInterne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FournisseurInterneController extends Controller
{
    /**
     * Resolve the complexe owned by the authenticated gérant.
     * Returns null if user has no owned complexe.
     */
    private function resolveComplexe(): ?Complexe
    {
        $user = auth('api')->user();
        if (!$user) return null;
        if ($user->role === 'super_admin') return null; // super_admin sees all
        return Complexe::where('owner_id', $user->id)->first();
    }

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $actif  = $request->query('actif');

        $query = FournisseurInterne::withCount('bonEntrees');

        // Scope to gerant's complexe (super_admin sees all)
        $complexe = $this->resolveComplexe();
        if ($complexe) {
            $query->where('complexe_id', $complexe->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_f_int', 'like', "%{$search}%")
                    ->orWhere('contact_f_int', 'like', "%{$search}%")
                    ->orWhere('email_f_int', 'like', "%{$search}%");
            });
        }

        if ($actif !== null) {
            $query->where('active', $actif === 'true' || $actif === '1');
        }

        $fournisseurs = $query->orderByDesc('active')->orderBy('nom_f_int')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $fournisseurs->items(),
            'meta'    => [
                'total'        => $fournisseurs->total(),
                'per_page'     => $fournisseurs->perPage(),
                'current_page' => $fournisseurs->currentPage(),
                'last_page'    => $fournisseurs->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $complexe = $this->resolveComplexe();
        if (!$complexe && $user->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Aucun complexe associé à ce gérant.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom_f_int'               => 'required|string|max:255',
            'raison_sociale_f_int'    => 'nullable|string|max:255',
            'contact_f_int'           => 'nullable|string|max:255',
            'tel_f_int'               => 'nullable|string|max:30',
            'email_f_int'             => 'nullable|email|max:255',
            'adresse_f_int'           => 'nullable|string',
            'matricule_fiscale_f_int' => 'nullable|string|max:50',
            // super_admin can pass complexe_id explicitly
            'complexe_id'             => $user->role === 'super_admin' ? 'nullable|exists:complexes,id' : 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        // Always force the gerant's complexe (overrides any payload value)
        if ($complexe) {
            $data['complexe_id'] = $complexe->id;
        }

        $fournisseur = FournisseurInterne::create($data);

        return response()->json(['success' => true, 'data' => $fournisseur], 201);
    }

    public function show(FournisseurInterne $fournisseurs_interne): JsonResponse
    {
        // Ensure gerant can only view their own complexe's suppliers
        $complexe = $this->resolveComplexe();
        if ($complexe && $fournisseurs_interne->complexe_id !== $complexe->id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $fournisseurs_interne->load('bonEntrees');

        return response()->json(['success' => true, 'data' => $fournisseurs_interne]);
    }

    public function update(Request $request, FournisseurInterne $fournisseurs_interne): JsonResponse
    {
        // Ensure gerant can only update their own complexe's suppliers
        $complexe = $this->resolveComplexe();
        if ($complexe && $fournisseurs_interne->complexe_id !== $complexe->id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom_f_int'               => 'required|string|max:255',
            'raison_sociale_f_int'    => 'nullable|string|max:255',
            'contact_f_int'           => 'nullable|string|max:255',
            'tel_f_int'               => 'nullable|string|max:30',
            'email_f_int'             => 'nullable|email|max:255',
            'adresse_f_int'           => 'nullable|string',
            'matricule_fiscale_f_int' => 'nullable|string|max:50',
            'active'                  => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fournisseurs_interne->update($validator->validated());

        return response()->json(['success' => true, 'data' => $fournisseurs_interne]);
    }

    public function destroy(FournisseurInterne $fournisseurs_interne): JsonResponse
    {
        // Ensure gerant can only delete their own complexe's suppliers
        $complexe = $this->resolveComplexe();
        if ($complexe && $fournisseurs_interne->complexe_id !== $complexe->id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        if ($fournisseurs_interne->bonEntrees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce fournisseur interne car il a des bons d\'entrée liés. Désactivez-le plutôt.',
            ], 422);
        }

        $fournisseurs_interne->delete();

        return response()->json(['success' => true, 'message' => 'Fournisseur interne supprimé.']);
    }

    public function toggleActive(FournisseurInterne $fournisseurs_interne): JsonResponse
    {
        // Ensure gerant can only toggle their own complexe's suppliers
        $complexe = $this->resolveComplexe();
        if ($complexe && $fournisseurs_interne->complexe_id !== $complexe->id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $fournisseurs_interne->update(['active' => !$fournisseurs_interne->active]);

        return response()->json(['success' => true, 'data' => $fournisseurs_interne]);
    }
}
