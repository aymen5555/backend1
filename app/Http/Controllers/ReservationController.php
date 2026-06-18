<?php

namespace App\Http\Controllers;

use App\Models\Complexe;
use App\Models\ReglementReservation;
use App\Models\Reservation;
use App\Models\Terrain;
use App\Services\ReservationConflictService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationConflictService $conflicts
    ) {}

    public function index(Request $request): JsonResponse
    {
        Reservation::updateExpiredStatus();
        
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Reservation::with(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']);

        if ($user->isClient()) {
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
            'data'    => $query->latest('start_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'terrain_id' => 'required|exists:terrains,id',
            'start_at'   => 'required|date|after_or_equal:now',
            'end_at'     => 'required|date|after:start_at',
            'notes'      => 'nullable|string|max:1000',
            'modalite_paiement' => 'required|in:carte,especes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $terrain = Terrain::with('complexe')->findOrFail($request->terrain_id);

        if (!$terrain->is_active || !$terrain->complexe->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This court is not available for booking.',
            ], 422);
        }

        $startAt = Carbon::parse($request->start_at);
        $endAt = Carbon::parse($request->end_at);

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

        if ($this->conflicts->hasConflict($terrain->id, $startAt, $endAt)) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot has already been booked.',
            ], 409);
        }

        $user = auth('api')->user();
        $complexeId = $terrain->complexe_id;

        // Adhérent discount: 20% off for active subscribers at this complexe
        $prixBase = $terrain->price_per_hour ?? 0;
        $prix = $user->isAdherentAt($complexeId) ? round($prixBase * 0.80, 2) : $prixBase;

        $reservation = Reservation::create([
            'user_id'           => auth('api')->id(),
            'terrain_id'        => $terrain->id,
            'start_at'          => $startAt,
            'end_at'            => $endAt,
            'notes'             => $request->notes,
            'status'            => 'pending',
            'modalite_paiement' => $request->modalite_paiement,
            'statut_paiement'   => 'non_paye',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation created successfully.',
            'data'    => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email']),
            'montant_a_payer' => $prix,
        ], 201);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        return response()->json([
            'success' => true,
            'data'    => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        $validator = Validator::make($request->all(), [
            'start_at' => 'sometimes|date|after_or_equal:now',
            'end_at'   => 'sometimes|date|after:start_at',
            'notes'    => 'nullable|string|max:1000',
            'status'   => 'sometimes|in:pending,confirmed,cancelled,expired,played',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['start_at']) || isset($data['end_at'])) {
            $startAt = Carbon::parse($data['start_at'] ?? $reservation->start_at);
            $endAt = Carbon::parse($data['end_at'] ?? $reservation->end_at);

            $now = Carbon::now('Africa/Tunis');
            if ($startAt->lt($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify a reservation that has already started.',
                ], 422);
            }

            $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);

            if (!$terrain->is_active || !$terrain->complexe->is_active) {
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
            'data'    => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function cancel(Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        if (!in_array($reservation->status, ['pending', 'confirmed', 'expired', 'played'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only active bookings can be cancelled.',
            ], 422);
        }

        $user = JWTAuth::user();
        $isPaid = $reservation->statut_paiement === 'paye';
        $montant = $reservation->montant_paye ?? $reservation->terrain->price_per_hour ?? 0;

        // Clients cannot cancel within 2 hours of start time
        // GERANT and SUPER_ADMIN can always cancel
        if (!$user->isGerantOrAdmin()) {
            $startAt = Carbon::parse($reservation->start_at);
            if (Carbon::now('Africa/Tunis')->diffInHours($startAt, false) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Annulation impossible moins de 2h avant le début',
                ], 422);
            }
        }

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type'           => 'remboursement',
            'montant'        => $montant,
            'reference'      => 'client_cancelled',
        ]);

        $reservation->update([
            'status'          => 'cancelled',
            'statut_paiement' => 'rembourse',
        ]);

        if ($isPaid) {
            return response()->json([
                'success' => true,
                'message' => "Réservation annulée. Remboursement de {$montant} DT initié.",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.',
        ]);
    }

    public function pay(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        if ($reservation->statut_paiement === 'paye' || $reservation->statut_paiement === 'rembourse') {
            return response()->json([
                'success' => false,
                'message' => 'Action impossible sur cette réservation',
            ], 422);
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations en attente peuvent être payées',
            ], 422);
        }

        if ($reservation->modalite_paiement !== 'carte') {
            return response()->json(['success' => false, 'message' => 'Cette réservation n\'admet pas de paiement en ligne'], 422);
        }

        $user = $reservation->user;
        $complexeId = $reservation->terrain->complexe_id;
        $prixBase = $reservation->terrain->price_per_hour ?? 0;
        $montant = $user->isAdherentAt($complexeId) ? round($prixBase * 0.80, 2) : $prixBase;

        $reservation->update([
            'status'          => 'confirmed',
            'statut_paiement' => 'paye',
            'montant_paye'    => $montant,
        ]);

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type'           => 'paiement',
            'montant'        => $montant,
            'reference'      => $request->reference_paiement ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully.',
            'data'    => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email']),
        ]);
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->authorizeAccess($reservation);

        if (!in_array($reservation->status, ['cancelled', 'expired', 'played'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only cancelled, expired, or completed reservations can be deleted.',
            ], 422);
        }

        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
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
            if (!$terrain || !in_array($terrain->complexe_id, $myComplexeIds->toArray())) {
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
