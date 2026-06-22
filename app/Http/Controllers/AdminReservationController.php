<?php

namespace App\Http\Controllers;

use App\Models\ReglementReservation;
use App\Models\Reservation;
use App\Models\Terrain;
use App\Models\User;
use App\Services\ReservationConflictService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminReservationController extends Controller
{
    public function __construct(
        private readonly ReservationConflictService $conflicts
    ) {}

    public function manualStore(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'terrain_id'        => 'required|exists:terrains,id',
            'date_seance_res'   => 'required|date_format:Y-m-d',
            'heure_debut_res'   => 'required|date_format:H:i',
            'heure_fin_res'     => 'required|date_format:H:i|after:heure_debut_res',
            'client_id'         => 'nullable|exists:users,id',
            'modalite_paiement' => 'required|in:carte,especes',
            'notes'             => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check terrain belongs to this gerant/admin
        $terrain = Terrain::with('complexe')->findOrFail($request->terrain_id);
        $complexe = $terrain->complexe;

        if (!$user->isAdmin() && $complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This court is not in your complex.',
            ], 403);
        }

        $clientId = $request->input('client_id') ?: $user->id;
        $client = User::find($clientId);
        if (!$client || ($client->isClient() === false && $clientId !== $user->id && !$client->hasVerifiedEmail())) {
            // Allow any client
        }

        $startAt = Carbon::createFromFormat('Y-m-d H:i', "{$request->date_seance_res} {$request->heure_debut_res}");
        $endAt   = Carbon::createFromFormat('Y-m-d H:i', "{$request->date_seance_res} {$request->heure_fin_res}");

        if ($endAt->lte($startAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['heure_fin_res' => ['L\'heure de fin doit être après l\'heure de début.']],
            ], 422);
        }

        if ($startAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation in the past.',
            ], 422);
        }

        if (!$terrain->is_active || !$complexe->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This court is not available for booking.',
            ], 422);
        }

        if ($this->conflicts->hasConflict($terrain->id, $startAt, $endAt)) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot is already booked.',
            ], 409);
        }

        $reservation = Reservation::create([
            'user_id'           => $clientId,
            'terrain_id'        => $terrain->id,
            'start_at'          => $startAt,
            'end_at'            => $endAt,
            'status'            => 'pending',
            'type'              => 'manual',
            'modalite_paiement' => $request->modalite_paiement,
            'statut_paiement'   => 'non_paye',
            'montant_paye'      => 0,
            'notes'             => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manual reservation created successfully.',
            'data'    => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email,phone']),
        ], 201);
    }

    public function confirmCash(Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check reservation is in user's complex
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (!$user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

        if ($reservation->modalite_paiement !== 'especes') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation n\'admet pas de paiement en espèces.',
            ], 422);
        }

        if ($reservation->statut_paiement !== 'non_paye') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà payée.',
            ], 422);
        }

        $client = $reservation->user;
        $complexeId = $terrain->complexe_id;
        $prixBase = $terrain->price_per_hour ?? 0;
        // Calculate price based on actual duration in hours
        $startAt = Carbon::parse($reservation->start_at);
        $endAt = Carbon::parse($reservation->end_at);
        $hours = max(1, round($startAt->diffInMinutes($endAt) / 60, 2));
        $montant = $client->isAdherentAt($complexeId) ? round($prixBase * $hours * 0.80, 2) : round($prixBase * $hours, 2);

        $reservation->update([
            'status'          => 'confirmed',
            'statut_paiement' => 'paye',
            'montant_paye'    => $montant,
        ]);

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type'           => 'paiement',
            'montant'        => $montant,
            'reference'      => 'cash_confirmed_by_admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as paid successfully.',
            'data'    => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function adminUpdate(Request $request, Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check if user owns the complex this reservation is for
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (!$user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'   => 'sometimes|in:pending,confirmed,cancelled,expired,played',
            'notes'    => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $reservation->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data'    => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email,phone', 'reglements']),
        ]);
    }

    public function confirmCardPayment(Request $request, Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check reservation belongs to user's complex
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (!$user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reference' => ['required', 'string', 'max:30', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
            'montant'   => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($reservation->modalite_paiement !== 'carte') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation n\'admet pas de paiement par carte.',
            ], 422);
        }

        if ($reservation->statut_paiement !== 'non_paye') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation est déjà payée.',
            ], 422);
        }

        $montant = $request->input('montant');
        $reference = $request->input('reference');

        $reservation->update([
            'status'          => 'confirmed',
            'statut_paiement' => 'paye',
            'montant_paye'    => $montant,
        ]);

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type'           => 'paiement',
            'montant'        => $montant,
            'reference'      => $reference,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data'    => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function adminDestroy(Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check if user owns the complex this reservation is for
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (!$user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

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
}
