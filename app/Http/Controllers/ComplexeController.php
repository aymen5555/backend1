<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Galerie;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ComplexeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if ($request->boolean('unassigned')) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains', 'images'])
                ->where(function ($query) {
                    $query->whereNull('owner_id')
                        ->orWhereHas('owner', function ($q) {
                            $q->where('role', '!=', 'gerant');
                        })
                        ->orWhereDoesntHave('owner');
                })
                ->where('is_active', true)
                ->latest()
                ->get();

            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Gerant admin view: only their own complexes
        if ($user && $user->isGerant()) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains', 'images'])
                ->where('owner_id', $user->id)
                ->latest()
                ->get();

            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Super admin sees all
        if ($user && $user->isAdmin()) {
            $complexes = Complexe::withCount('terrains')
                ->with(['owner:id,first_name,last_name,email', 'terrains', 'images'])
                ->latest()
                ->get();

            return response()->json(['success' => true, 'data' => $complexes]);
        }

        // Public / client: only active complexes
        $complexes = Complexe::withCount('terrains')
            ->with(['owner:id,first_name,last_name,email', 'terrains', 'images'])
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $complexes]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // GERANT and SUPER_ADMIN can create complexes
        if (! $user || ! $user->isGerantOrAdmin()) {
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
            'gallery_images' => $request->gallery_images ?: null,
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100',
            'description' => 'nullable|string|max:1000',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1000',
            'facebook_url' => 'nullable|url|max:1000',
            'instagram_url' => 'nullable|url|max:1000',
            'website_url' => 'nullable|url|max:1000',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
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
        $galleryImages = null;
        if (array_key_exists('gallery_images', $validated)) {
            $galleryImages = $validated['gallery_images'];
            unset($validated['gallery_images']);
        }

        if (array_key_exists('website_url', $validated)) {
            $validated['website_c'] = $validated['website_url'];
            unset($validated['website_url']);
        }

        $complexe = Complexe::create([
            ...$validated,
            'owner_id' => auth('api')->id(),
        ]);

        if ($galleryImages !== null) {
            $this->syncGalleryImages($complexe, $galleryImages);
        }

        return response()->json([
            'success' => true,
            'message' => 'Complex created successfully.',
            'data' => $complexe->load(['owner:id,first_name,last_name,email', 'images']),
        ], 201);
    }

    public function show(Complexe $complexe): JsonResponse
    {
        $user = auth('api')->user();

        // Super admin can see any complex
        if ($user && $user->isAdmin()) {
            return response()->json([
                'success' => true,
                'data' => $complexe->load(['owner:id,first_name,last_name,email', 'terrains', 'images']),
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
                'data' => $complexe->load(['owner:id,first_name,last_name,email', 'terrains', 'images']),
            ]);
        }

        // Clients, guests, subscribers — only active complexes
        if (! $complexe->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This complex is not available.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $complexe->load(['owner:id,first_name,last_name,email', 'terrains', 'images']),
        ]);
    }

    public function availability(Request $request, Complexe $complexe): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'timezone' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->date;
        $timezone = $request->filled('timezone') ? $request->timezone : config('app.timezone');

        $result = [];

        foreach ($complexe->terrains as $terrain) {
            $openHour = $terrain->heure_ouverture ? (int) date('G', strtotime($terrain->heure_ouverture)) : 8;
            $closeHour = $terrain->heure_fermeture ? (int) date('G', strtotime($terrain->heure_fermeture)) : 22;

            $sessionMinutes = $terrain->nbminute_seance ?: 0;
            $sessionHours = $terrain->nbheures_seance ?: 1;
            $stepMinutes = ($sessionHours * 60) + $sessionMinutes;

            $slots = [];
            for ($mins = $openHour * 60; $mins + $stepMinutes <= $closeHour * 60; $mins += $stepMinutes) {
                $hours = intdiv($mins, 60);
                $minutes = $mins % 60;
                $time = sprintf('%02d:%02d', $hours, $minutes);

                $startAt = Carbon::parse("{$date} {$time}:00", $timezone);
                $endAt = $startAt->copy()->addMinutes($stepMinutes);

                if ($startAt->isPast()) {
                    continue;
                }

                $hasConflict = Reservation::query()
                    ->where('terrain_id', $terrain->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt)
                    ->exists();

                $slots[] = [
                    'time' => $time,
                    'starts_at' => $startAt->toIso8601String(),
                    'ends_at' => $endAt->toIso8601String(),
                    'timezone' => $timezone,
                    'available' => ! $hasConflict,
                ];
            }

            $result[] = [
                'terrain_id' => $terrain->id,
                'terrain_name' => $terrain->name,
                'price_per_hour' => $terrain->price_per_hour,
                'slots' => $slots,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'complexe_id' => $complexe->id,
                'complexe_name' => $complexe->name,
                'date' => $date,
                'timezone' => $timezone,
                'terrains' => $result,
            ],
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
            'gallery_images' => $request->gallery_images ?: null,
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:100',
            'description' => 'nullable|string|max:1000',
            'address' => 'sometimes|string|max:255',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1000',
            'facebook_url' => 'nullable|url|max:1000',
            'instagram_url' => 'nullable|url|max:1000',
            'website_url' => 'nullable|url|max:1000',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $galleryImages = null;
        if (array_key_exists('gallery_images', $validated)) {
            $galleryImages = $validated['gallery_images'];
            unset($validated['gallery_images']);
        }

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

        if ($request->has('gallery_images')) {
            $this->syncGalleryImages($complexe, $galleryImages ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Complex updated successfully.',
            'data' => $complexe->fresh()->load(['terrains', 'images']),
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

    private function syncGalleryImages(Complexe $complexe, array $galleryImages): void
    {
        $complexe->images()->delete();

        foreach (array_values(array_filter($galleryImages, fn ($url) => is_string($url) && trim($url) !== '')) as $index => $imageUrl) {
            Galerie::create([
                'complexe_id' => $complexe->id,
                'image_g' => trim($imageUrl),
                'ordre' => $index,
            ]);
        }
    }

    private function authorizeOwner(Complexe $complexe): void
    {
        $user = auth('api')->user();

        if (! $user) {
            abort(response()->json([
                'success' => false,
                'message' => 'Forbidden. Authentication required.',
            ], 403));
        }

        // Super admin can manage any complex
        if ($user->isAdmin()) {
            return;
        }

        if ($complexe->owner_id !== $user->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not own this complex.',
            ], 403));
        }
    }
}
