<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\Reservation;
use App\Services\ReservationConflictService;
use App\Models\Terrain;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use function auth;

class TerrainController extends Controller
{
    public function __construct(private readonly ReservationConflictService $conflicts)
    {
    }
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complexe_id' => 'nullable|exists:complexes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Terrain::with('complexe:id,name,city,is_active,owner_id')
            ->whereIn('complexe_id', $myComplexeIds);

        if ($request->filled('complexe_id')) {
            $query->where('complexe_id', $request->complexe_id);
        }

        if ($user && $user->isAdmin()) {
            // Super admin sees ALL terrains across ALL complexes
        } elseif ($user && $user->isGerant()) {
            // Gerant sees terrains in their own complexes only
        } else {
            // Clients, guests, subscribers see active terrains in active complexes
            $query->where('is_active', true)
                ->whereHas('complexe', fn ($q) => $q->where('is_active', true));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can create courts.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'name' => 'required|string|min:2|max:100',
            'sport_type' => 'sometimes|string|max:50',
            'price_per_hour' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $complexe = Complexe::whereIn('id', $myComplexeIds)->findOrFail($request->complexe_id);
        $this->authorizeOwner($complexe);

        $validated = $validator->validated();

        if (array_key_exists('image_url', $validated)) {
            $validated['image_t'] = $validated['image_url'];
            unset($validated['image_url']);
        }

        $terrain = Terrain::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Court created successfully.',
            'data' => $terrain->load('complexe:id,name,city'),
        ], 201);
    }

    public function show(Terrain $terrain): JsonResponse
    {
        $this->authorizeAccess($terrain);

        return response()->json([
            'success' => true,
            'data' => $terrain->load('complexe'),
        ]);
    }

    public function update(Request $request, Terrain $terrain): JsonResponse
    {
        $this->authorizeOwner($terrain->complexe);

        $request->merge([
            'image_url' => $request->image_url ?: null,
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:2|max:100',
            'sport_type' => 'sometimes|string|max:50',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'image_url' => 'nullable|url|max:1000',
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
            $validated['image_t'] = $validated['image_url'];
            unset($validated['image_url']);
        }

        $terrain->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Court updated successfully.',
            'data' => $terrain->fresh()->load('complexe'),
        ]);
    }

    public function destroy(Terrain $terrain): JsonResponse
    {
        $this->authorizeOwner($terrain->complexe);
        $terrain->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Court désactivé sans suppression des données.',
        ]);
    }

    private function authorizeAccess(Terrain $terrain): void
    {
        $user = auth('api')->user();
        $complexe = $terrain->complexe;

        // Super admin can access any terrain
        if ($user && $user->isAdmin()) {
            return;
        }

        // Gerant can access their own terrains
        if ($user && $user->isGerant()) {
            if ($complexe->owner_id !== $user->id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not own this court.',
                ], 403));
            }

            return;
        }

        // Clients, guests, subscribers — only active
        if ((! $user || $user->isClient()) && (! $terrain->is_active || ! $complexe?->is_active)) {
            abort(response()->json([
                'success' => false,
                'message' => 'This court is not available.',
            ], 404));
        }
    }

    public function slots(Request $request, Terrain $terrain): JsonResponse
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

        // Use terrain-specific opening hours, with fallbacks
        $openHour = $terrain->heure_ouverture ? (int) date('G', strtotime($terrain->heure_ouverture)) : 8;
        $closeHour = $terrain->heure_fermeture ? (int) date('G', strtotime($terrain->heure_fermeture)) : 22;

        // Use the terrain session duration, default to 1 hour
        $sessionMinutes = $terrain->nbminute_seance ?: 0;
        $sessionHours = $terrain->nbheures_seance ?: 1;
        $stepMinutes = ($sessionHours * 60) + $sessionMinutes;

        $slots = [];
        $user = auth('api')->user();
        for ($mins = $openHour * 60; $mins + $stepMinutes <= $closeHour * 60; $mins += $stepMinutes) {
            $hours = intdiv($mins, 60);
            $minutes = $mins % 60;
            $time = sprintf('%02d:%02d', $hours, $minutes);

            $requestedTz = $timezone ?: config('app.timezone');

            // Generate slot base times using server timezone to match terrain opening hours
            $startAt = Carbon::parse("{$date} {$time}:00", config('app.timezone'));
            $endAt = $startAt->copy()->addMinutes($stepMinutes);

            // Skip slots in the past using server timezone
            if ($startAt->isPast()) {
                continue;
            }

            // Check if slot is available (occupied by others) for this terrain
            $hasConflict = Reservation::query()
                ->where('terrain_id', $terrain->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_at', '<', $endAt)
                ->where('end_at', '>', $startAt)
                ->exists();

            // Also consider whether the current authenticated user has an overlapping reservation elsewhere
            $userConflict = false;
            if ($user) {
                $userConflict = $this->conflicts->hasUserConflict($user->id, $startAt, $endAt);
            }

            $slots[] = [
                'time' => $time,
                'starts_at' => $startAt->copy()->setTimezone($requestedTz)->toIso8601String(),
                'ends_at' => $endAt->copy()->setTimezone($requestedTz)->toIso8601String(),
                'timezone' => $requestedTz,
                'available' => (! $hasConflict) && (! $userConflict),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'terrain_id' => $terrain->id,
                'terrain_name' => $terrain->name,
                'date' => $date,
                'timezone' => $timezone,
                'slots' => $slots,
            ],
        ]);
    }

    private function authorizeOwner(Complexe $complexe): void
    {
        $user = auth('api')->user();

        // Super admin can manage any terrain
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
