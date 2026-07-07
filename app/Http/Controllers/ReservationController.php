<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\ReglementReservation;
use App\Models\Reservation;
use App\Models\Terrain;
use App\Models\User;
use App\Services\PricingService;
use App\Services\ReservationConflictService;
use App\Services\ReservationLockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationConflictService $conflicts,
        private readonly ReservationLockService $locks,
        private readonly PricingService $pricing
    ) {}

    public function index(Request $request): JsonResponse
    {
        Reservation::updateExpiredStatus();

        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Reservation::with(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']);

        if ($user->isClient() || $request->boolean('mine')) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereHas('terrain', fn ($q) => $q->whereIn('complexe_id', $myComplexeIds));
        }

        if ($request->filled('terrain_id')) {
            $query->where('terrain_id', $request->terrain_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservations loaded successfully.',
            'data' => $query->latest('start_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'terrain_id' => 'required|exists:terrains,id',
            'start_at' => 'required|date|after_or_equal:now',
            'end_at' => 'required|date|after:start_at',
            'notes' => 'nullable|string|max:1000',
            'modalite_paiement' => 'required|in:carte,especes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $terrain = Terrain::with('complexe')->findOrFail($request->terrain_id);

        if (! $terrain->is_active || ! $terrain->complexe?->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This court is not available for booking.',
            ], 422);
        }

        $startAt = Carbon::parse($request->start_at)->setTimezone(config('app.timezone'));
        $endAt = Carbon::parse($request->end_at)->setTimezone(config('app.timezone'));

        if ($terrain->heure_ouverture && $terrain->heure_fermeture) {
            $startMinutes = $startAt->hour * 60 + $startAt->minute;
            $endMinutes = $endAt->hour * 60 + $endAt->minute;
            $openMinutes = Carbon::parse($terrain->heure_ouverture)->hour * 60 + Carbon::parse($terrain->heure_ouverture)->minute;
            $closeMinutes = Carbon::parse($terrain->heure_fermeture)->hour * 60 + Carbon::parse($terrain->heure_fermeture)->minute;

            if ($startMinutes < $openMinutes || $endMinutes > $closeMinutes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation time must be within court opening hours.',
                ], 422);
            }
        }

        $reservation = $this->locks->executeWithTerrainLock($terrain->id, function () use ($terrain, $startAt, $endAt, $request) {
            if ($this->conflicts->hasConflict($terrain->id, $startAt, $endAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot has already been booked.',
                ], 409);
            }

            $user = auth('api')->user();
            $complexeId = $terrain->complexe_id;

            // Adhérent discount via PricingService
            $prix = $this->pricing->calculate(
                $terrain->price_per_hour ?? 0,
                $startAt,
                $endAt,
                $user,
                $complexeId
            );

            $reservation = Reservation::create([
                'user_id' => auth('api')->id(),
                'terrain_id' => $terrain->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'notes' => $request->notes,
                'status' => 'pending',
                'modalite_paiement' => $request->modalite_paiement,
                'statut_paiement' => 'non_paye',
                'montant_paye' => $prix,
            ]);

            // Notify complex owner (gérant)
            $owner = $terrain->complexe?->owner;
            if ($owner) {
                $owner->notify(new \App\Notifications\NewReservationCreated($reservation));
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully.',
                'data' => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email']),
            ], 201);
        });

        if ($reservation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to acquire reservation lock. Please retry.',
            ], 503);
        }

        return $reservation;
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        return response()->json([
            'success' => true,
            'message' => 'Reservation loaded successfully.',
            'data' => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        $validator = Validator::make($request->all(), [
            'start_at' => 'sometimes|date|after_or_equal:now',
            'end_at' => 'sometimes|date|after:start_at',
            'notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,confirmed,cancelled,expired,played',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['start_at']) || isset($data['end_at'])) {
            $startAt = Carbon::parse($data['start_at'] ?? $reservation->start_at)->setTimezone(config('app.timezone'));
            $endAt = Carbon::parse($data['end_at'] ?? $reservation->end_at)->setTimezone(config('app.timezone'));

            $now = Carbon::now('Africa/Tunis');
            if ($startAt->lt($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify a reservation that has already started.',
                ], 422);
            }

            $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);

            if (! $terrain->is_active || ! $terrain->complexe?->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This court is not available for booking.',
                ], 422);
            }

            if ($terrain->heure_ouverture && $terrain->heure_fermeture) {
                $startMinutes = $startAt->hour * 60 + $startAt->minute;
                $endMinutes = $endAt->hour * 60 + $endAt->minute;
                $openMinutes = Carbon::parse($terrain->heure_ouverture)->hour * 60 + Carbon::parse($terrain->heure_ouverture)->minute;
                $closeMinutes = Carbon::parse($terrain->heure_fermeture)->hour * 60 + Carbon::parse($terrain->heure_fermeture)->minute;

                if ($startMinutes < $openMinutes || $endMinutes > $closeMinutes) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reservation time must be within court opening hours.',
                    ], 422);
                }
            }

            if ($this->conflicts->hasConflict($reservation->terrain_id, $startAt, $endAt, $reservation->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot has already been booked.',
                ], 409);
            }

            $data['start_at'] = $startAt;
            $data['end_at'] = $endAt;
        }

        $reservation->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data' => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        $isForce = $request->boolean('force');

        return DB::transaction(function () use ($reservation, $isForce): JsonResponse {
            $fresh = Reservation::lockForUpdate()->find($reservation->id);

            if (! in_array($fresh->status, ['pending', 'confirmed', 'expired', 'played'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réservation est déjà annulée.',
                ], 409);
            }

            $user = JWTAuth::user();
            $isPaid = $fresh->statut_paiement === 'paye' && $fresh->montant_paye > 0;
            $montant = $fresh->montant_paye ?? 0;

            if ($fresh->modalite_paiement === 'carte' && $fresh->statut_paiement === 'non_paye') {
                $fresh->update(['status' => 'cancelled']);

                return response()->json([
                    'success' => true,
                    'message' => 'Réservation annulée — paiement non effectué.',
                    'data' => null,
                ]);
            }

            if (! $isForce && ! $user->isGerantOrAdmin()) {
                $startAt = Carbon::parse($fresh->start_at)->setTimezone(config('app.timezone'));
                $now = Carbon::now(config('app.timezone'));

                if ($now->gte($startAt)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot cancel a reservation that has already started.',
                    ], 422);
                }

                if (abs($startAt->diffInHours($now, false)) < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Annulation impossible moins de 2h avant le début',
                    ], 422);
                }
            }

            if ($isPaid) {
                ReglementReservation::create([
                    'reservation_id' => $fresh->id,
                    'type' => 'remboursement',
                    'montant' => $montant,
                    'reference' => 'CANCEL-'.$fresh->id.'-'.now()->format('Ymd'),
                ]);
            }

            $fresh->update([
                'status' => 'cancelled',
                'statut_paiement' => $isPaid ? 'rembourse' : $fresh->statut_paiement,
            ]);

            return response()->json([
                'success' => true,
                'message' => $isPaid
                    ? "Réservation annulée. Remboursement de {$montant} DT initié."
                    : 'Reservation cancelled successfully.',
                'data' => $fresh->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
            ]);
        });
    }

    public function pay(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        return DB::transaction(function () use ($reservation, $request): JsonResponse {
            $fresh = Reservation::lockForUpdate()->find($reservation->id);

            if ($fresh->statut_paiement === 'paye') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réservation est déjà payée.',
                ], 422);
            }

            if ($fresh->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les réservations en attente peuvent être payées',
                ], 422);
            }

            if ($fresh->modalite_paiement !== 'carte') {
                return response()->json(['success' => false, 'message' => 'Cette réservation n\'admet pas de paiement en ligne'], 422);
            }

            $client = User::find($fresh->user_id);
            $complexeId = $fresh->terrain->complexe_id;
            $montant = $this->pricing->calculate(
                $fresh->terrain->price_per_hour ?? 0,
                Carbon::parse($fresh->start_at),
                Carbon::parse($fresh->end_at),
                $client,
                $complexeId
            );

            $fresh->update([
                'status' => 'confirmed',
                'statut_paiement' => 'paye',
                'montant_paye' => $montant,
                'reference_paiement' => $request->reference_paiement ?? null,
            ]);

            ReglementReservation::create([
                'reservation_id' => $fresh->id,
                'type' => 'paiement',
                'montant' => $montant,
                'reference' => $request->reference_paiement ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully.',
                'data' => $fresh->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email']),
            ]);
        });
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        if (! in_array($reservation->status, ['cancelled', 'expired', 'played'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only cancelled, expired, or completed reservations can be archived.',
            ], 422);
        }

        // Always soft-delete for audit trail — archives show ALL deleted records
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réservation archivée.',
            'data' => null,
        ]);
    }

    private function authorizeAccess(Reservation $reservation): void
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        // Super admin can access any reservation
        if ($user && $user->isAdmin()) {
            return;
        }

        // Gerant and admin can access reservations for their own complexes/terrains
        if ($user->isGerant() || $user->isAdmin()) {
            $terrain = Terrain::with('complexe')->find($reservation->terrain_id);
            if (! $terrain || ! in_array($terrain->complexe_id, $myComplexeIds->toArray())) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Forbidden. This reservation is not in your complex.',
                ], 403));
            }

            return;
        }

        // Client can only access their own reservations
        if ($user->isClient() && $reservation->user_id !== $user->id) {
            abort(response()->json([
                'success' => false,
                'message' => 'Forbidden. This is not your reservation.',
            ], 403));
        }
    }
}
