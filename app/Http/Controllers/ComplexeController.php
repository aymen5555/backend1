<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ComplexeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if ($request->boolean('unassigned')) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains'])
                ->whereNull('owner_id')
                ->where('is_active', true)
                ->latest()
                ->get();

            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Gerant admin view: only their own complexes
        if ($user && $user->isGerant()) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains'])
                ->where('owner_id', $user->id)
                ->latest()
                ->get();
            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Super admin sees all
        if ($user && $user->isAdmin()) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains'])
                ->latest()
                ->get();
            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Public / client: only active complexes
        $complexes = Complexe::withCount('terrains')
            ->with(['owner:id,first_name,last_name,email', 'terrains'])
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $complexes]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // GERANT and SUPER_ADMIN can create complexes
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can create complexes.',
            ], 403);
        }

        $request->merge([
            'image_url' => $request->image_url ?: null,
            'facebook_url' => $request->facebook_url ?: null,
            'instagram_url' => $request->instagram_url ?: null,
            'website_url' => $request->website_url ?: null,
        ]);

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|min:2|max:100',
            'description'  => 'nullable|string|max:1000',
            'address'      => 'required|string|max:255',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'is_active'    => 'sometimes|boolean',
            'image_url'    => 'nullable|url|max:1000',
            'facebook_url' => 'nullable|url|max:1000',
            'instagram_url'=> 'nullable|url|max:1000',
            'website_url'  => 'nullable|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (array_key_exists('image_url', $validated)) {
            $validated['image_c'] = $validated['image_url'];
            unset($validated['image_url']);
        }
        if (array_key_exists('facebook_url', $validated)) {
            $validated['facebook_c'] = $validated['facebook_url'];
            unset($validated['facebook_url']);
        }
        if (array_key_exists('instagram_url', $validated)) {
            $validated['instagram_c'] = $validated['instagram_url'];
            unset($validated['instagram_url']);
        }
        if (array_key_exists('website_url', $validated)) {
            $validated['website_c'] = $validated['website_url'];
            unset($validated['website_url']);
        }

        $complexe = Complexe::create([
            ...$validated,
            'owner_id' => auth('api')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Complex created successfully.',
            'data'    => $complexe->load('owner:id,first_name,last_name,email'),
        ], 201);
    }

    public function show(Complexe $complexe): JsonResponse
    {
        $user = JWTAuth::user();

        // Super admin can see any complex
        if ($user && $user->isAdmin()) {
            return response()->json([
                'success' => true,
                'data'    => $complexe->load(['owner:id,first_name,last_name,email', 'terrains']),
            ]);
        }

        // Gerant can only see their own complexes
        if ($user && $user->isGerant()) {
            if ($complexe->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this complex.',
                ], 403);
            }
            return response()->json([
                'success' => true,
                'data'    => $complexe->load(['owner:id,first_name,last_name,email', 'terrains']),
            ]);
        }

        // Clients, guests, subscribers — only active complexes
        if (!$complexe->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This complex is not available.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $complexe->load(['owner:id,first_name,last_name,email', 'terrains']),
        ]);
    }

    public function update(Request $request, Complexe $complexe): JsonResponse
    {
        $this->authorizeOwner($complexe);

        $request->merge([
            'image_url' => $request->image_url ?: null,
            'facebook_url' => $request->facebook_url ?: null,
            'instagram_url' => $request->instagram_url ?: null,
            'website_url' => $request->website_url ?: null,
        ]);

        $validator = Validator::make($request->all(), [
            'name'         => 'sometimes|string|min:2|max:100',
            'description'  => 'nullable|string|max:1000',
            'address'      => 'sometimes|string|max:255',
            'city'         => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'is_active'    => 'sometimes|boolean',
            'image_url'    => 'nullable|url|max:1000',
            'facebook_url' => 'nullable|url|max:1000',
            'instagram_url'=> 'nullable|url|max:1000',
            'website_url'  => 'nullable|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (array_key_exists('image_url', $validated)) {
            $validated['image_c'] = $validated['image_url'];
            unset($validated['image_url']);
        }
        if (array_key_exists('facebook_url', $validated)) {
            $validated['facebook_c'] = $validated['facebook_url'];
            unset($validated['facebook_url']);
        }
        if (array_key_exists('instagram_url', $validated)) {
            $validated['instagram_c'] = $validated['instagram_url'];
            unset($validated['instagram_url']);
        }
        if (array_key_exists('website_url', $validated)) {
            $validated['website_c'] = $validated['website_url'];
            unset($validated['website_url']);
        }

        $complexe->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Complex updated successfully.',
            'data'    => $complexe->fresh()->load('terrains'),
        ]);
    }

    public function destroy(Complexe $complexe): JsonResponse
    {
        $this->authorizeOwner($complexe);
        $complexe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Complex deleted successfully.',
        ]);
    }

    private function authorizeOwner(Complexe $complexe): void
    {
        $user = auth('api')->user();

        // Super admin can manage any complex
        if ($user && $user->isAdmin()) {
            return;
        }

        if ($complexe->owner_id !== auth('api')->id()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not own this complex.',
            ], 403));
        }
    }
}