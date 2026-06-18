<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\User;
use App\Models\AbonnementAdherent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    public function stats(): JsonResponse
    {
        $users = User::count();
        $complexes = Complexe::count();
        $abonnements = AbonnementAdherent::count();

        return response()->json(['success' => true, 'data' => compact('users','complexes','abonnements')]);
    }

    // ──────────────────────────────────────────────
    //  POST /api/admin/gerants (super_admin only)
    // ──────────────────────────────────────────────
    public function createGerant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2|max:50',
            'last_name'  => 'required|string|min:2|max:50',
            'email'      => [
                'required',
                'string',
                'email:rfc',
                'max:100',
                'unique:users,email',
            ],
            'password'   => 'required|string|min:8',
            'complexe_id' => [
                'required',
                'integer',
                Rule::exists('complexes', 'id')->whereNull('owner_id'),
            ],
            'phone'      => 'nullable|string|max:20',
        ], [
            'email.unique'         => 'Cet email est déjà enregistré.',
            'complexe_id.required' => 'Veuillez sélectionner un complexe.',
            'complexe_id.exists'   => 'Le complexe sélectionné n\'est pas disponible ou est déjà assigné.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => strtolower($request->email),
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'role'       => 'gerant',
                'email_verified_at' => now(),
                'is_active'  => true,
            ]);

            $updated = Complexe::where('id', $request->complexe_id)
                ->whereNull('owner_id')
                ->update(['owner_id' => $user->id]);

            if ($updated === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Le complexe sélectionné n\'est plus disponible.',
                    'errors'  => ['complexe_id' => ['Le complexe est déjà assigné.']],
                ], 422);
            }

            DB::commit();

            $gerant = $user->load('complexe:id,owner_id,name,description,address');

            return response()->json([
                'success' => true,
                'message' => 'Gérant créé avec succès.',
                'data'    => [
                    'id'       => $gerant->id,
                    'first_name' => $gerant->first_name,
                    'last_name'  => $gerant->last_name,
                    'email'      => $gerant->email,
                    'phone'      => $gerant->phone,
                    'role'       => $gerant->role,
                    'complexe'   => $gerant->complexe,
                    'created_at' => $gerant->created_at,
                ],
            ], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    // ──────────────────────────────────────────────
    //  GET /api/admin/gerants (super_admin only)
    // ──────────────────────────────────────────────
    public function listGerants(): JsonResponse
    {
        $gerants = User::where('role', 'gerant')
            ->with('complexe:id,owner_id,name,description,address')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($g) => [
                'id'       => $g->id,
                'first_name' => $g->first_name,
                'last_name'  => $g->last_name,
                'email'      => $g->email,
                'phone'      => $g->phone,
                'is_active'  => $g->is_active,
                'complexe'   => $g->complexe,
                'created_at' => $g->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $gerants,
        ]);
    }

    // ──────────────────────────────────────────────
    //  PATCH /api/admin/gerants/{gerant} (super_admin only)
    // ──────────────────────────────────────────────
    public function deactivateGerant(Request $request, User $gerant): JsonResponse
    {
        if ($gerant->role !== 'gerant') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un gérant.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Release the complexe back to unassigned
            if ($gerant->complexe) {
                Complexe::where('owner_id', $gerant->id)
                    ->update(['owner_id' => null]);
            }

            $gerant->update(['is_active' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gérant désactivé avec succès. Le complexe a été libéré.',
                'data'    => ['id' => $gerant->id, 'is_active' => $gerant->is_active],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ──────────────────────────────────────────────
    //  POST /api/admin/gerants/{gerant}/activate (super_admin only)
    // ──────────────────────────────────────────────
    public function activateGerant(Request $request, User $gerant): JsonResponse
    {
        if ($gerant->role !== 'gerant') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un gérant.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $gerant->update(['is_active' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gérant activé avec succès.',
                'data'    => ['id' => $gerant->id, 'is_active' => $gerant->is_active],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ──────────────────────────────────────────────
    //  PUT /api/admin/gerants/{gerant}/complexe (super_admin only)
    //  Assign or unassign a complexe to/from a gerant
    //  Body: { "complexe_id": number | null }
    // ──────────────────────────────────────────────
    public function assignComplexe(Request $request, User $gerant): JsonResponse
    {
        if ($gerant->role !== 'gerant') {
            return response()->json([
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un gérant.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'complexe_id' => [
                'nullable',
                'integer',
                Rule::exists('complexes', 'id'),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $newComplexeId = $request->complexe_id;

        DB::beginTransaction();
        try {
            // 1. Unassign current complexe if any
            if ($gerant->complexe) {
                Complexe::where('owner_id', $gerant->id)
                    ->update(['owner_id' => null]);
            }

            // 2. Assign new complexe if provided
            if ($newComplexeId) {
                // Lock the target complexe row to avoid concurrent assignments
                $target = Complexe::where('id', $newComplexeId)->lockForUpdate()->first();

                if (!$target) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Complexe introuvable.',
                        'errors'  => ['complexe_id' => ['Complexe introuvable.']],
                    ], 422);
                }

                // If it's already owned by another gerant, fail
                if ($target->owner_id && $target->owner_id !== $gerant->id) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce complexe est déjà assigné à un autre gérant.',
                        'errors'  => ['complexe_id' => ['Complexe déjà assigné.']],
                    ], 422);
                }

                // Assign to current gerant
                $target->owner_id = $gerant->id;
                $target->save();
            }

            DB::commit();

            $gerant->load('complexe:id,owner_id,name,description,address');

            return response()->json([
                'success' => true,
                'message' => $newComplexeId
                    ? 'Complexe assigné au gérant avec succès.'
                    : 'Complexe retiré du gérant avec succès.',
                'data'    => [
                    'id'         => $gerant->id,
                    'first_name' => $gerant->first_name,
                    'last_name'  => $gerant->last_name,
                    'email'      => $gerant->email,
                    'phone'      => $gerant->phone,
                    'is_active'  => $gerant->is_active,
                    'complexe'   => $gerant->complexe,
                    'created_at' => $gerant->created_at,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
