<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Validator;

class AdminReservationController extends Controller
{
    public function __construct(
        private readonly ReservationConflictService $conflicts,
        private readonly ReservationLockService $locks,
        private readonly PricingService $pricing
    ) {}

    public function manualStore(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'terrain_id' => 'required|exists:terrains,id',
            'date_seance_res' => 'required|date_format:Y-m-d',
            'heure_debut_res' => 'required|date_format:H:i',
            'heure_fin_res' => 'required|date_format:H:i|after:heure_debut_res',
            'client_id' => 'required|exists:users,id',
            'modalite_paiement' => 'required|in:carte,especes',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check terrain belongs to this gerant/admin
        $terrain = Terrain::with('complexe')->findOrFail($request->terrain_id);
        $complexe = $terrain->complexe;

        if (! $user->isAdmin() && $complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This court is not in your complex.',
            ], 403);
        }

        $clientId = $request->client_id;
        $client = User::findOrFail($clientId);

        $startAt = Carbon::createFromFormat('Y-m-d H:i', "{$request->date_seance_res} {$request->heure_debut_res}");
        $endAt = Carbon::createFromFormat('Y-m-d H:i', "{$request->date_seance_res} {$request->heure_fin_res}");

        if ($endAt->lte($startAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => ['heure_fin_res' => ['L\'heure de fin doit être après l\'heure de début.']],
            ], 422);
        }

        if ($startAt->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation in the past.',
            ], 422);
        }

        if (! $terrain->is_active || ! $complexe->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This court is not available for booking.',
            ], 422);
        }

        $prixBase = $terrain->price_per_hour ?? 0;
        // CRITICAL: use $client (the booking client) NOT auth()->user() (the admin)
        $prix = $this->pricing->calculate($prixBase, $startAt, $endAt, $client, $complexe->id);

        $reservation = $this->locks->executeWithTerrainLock($terrain->id, function () use ($terrain, $startAt, $endAt, $request, $clientId, $prix) {
            if ($this->conflicts->hasConflict($terrain->id, $startAt, $endAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot is already booked.',
                ], 409);
            }

            return Reservation::create([
                'user_id' => $clientId,
                'terrain_id' => $terrain->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'pending',
                'type' => 'manual',
                'modalite_paiement' => $request->modalite_paiement,
                'statut_paiement' => 'non_paye',
                'montant_paye' => $prix,
                'notes' => $request->notes,
            ]);
        });

        if ($reservation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to acquire reservation lock. Please retry.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Manual reservation created successfully.',
            'data' => $reservation->load(['terrain.complexe', 'user:id,first_name,last_name,email,phone']),
        ], 201);
    }

    public function confirmCash(Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check reservation is in user's complex
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (! $user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
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

        $client = User::find($reservation->user_id); // CRITICAL: use the booking client, not auth() admin
        $complexeId = $terrain->complexe_id;
        $montant = $this->pricing->calculate(
            $terrain->price_per_hour ?? 0,
            Carbon::parse($reservation->start_at),
            Carbon::parse($reservation->end_at),
            $client,
            $complexeId
        );

        $reservation->update([
            'status' => 'confirmed',
            'statut_paiement' => 'paye',
            'montant_paye' => $montant,
        ]);

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type' => 'paiement',
            'montant' => $montant,
            'reference' => 'cash_confirmed_by_admin',
        ]);

        if ($reservation->user) {
            $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation, "Le paiement en espèces de votre réservation du " . ($reservation->date_seance_res ?? '') . " a été confirmé."));
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment marked as paid successfully.',
            'data' => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function adminUpdate(Request $request, Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check if user owns the complex this reservation is for
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (! $user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,cancelled,expired,played',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $reservation->update($data);

        if ($reservation->user && isset($data['status'])) {
            $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'data' => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email,phone', 'reglements']),
        ]);
    }

    public function confirmCardPayment(Request $request, Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check reservation belongs to user's complex
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (! $user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reference' => ['nullable', 'string', 'max:30', 'regex:/^TXN-\d{4}-\d{3,8}$/i'],
            'montant' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
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

        $montant = $request->input('montant') ?? $reservation->montant_paye ?? $reservation->tarif_calcule ?? 0;
        $reference = $request->input('reference') ?? ('TXN-' . now()->format('Y') . '-' . rand(10000, 99999));

        $reservation->update([
            'status' => 'confirmed',
            'statut_paiement' => 'paye',
            'montant_paye' => $montant,
        ]);

        ReglementReservation::create([
            'reservation_id' => $reservation->id,
            'type' => 'paiement',
            'montant' => $montant,
            'reference' => $reference,
        ]);

        if ($reservation->user) {
            $reservation->user->notify(new \App\Notifications\ReservationStatusChanged($reservation, "Le paiement par carte de votre réservation du " . ($reservation->date_seance_res ?? '') . " a été confirmé."));
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => $reservation->fresh()->load(['terrain.complexe', 'user:id,first_name,last_name,email', 'reglements']),
        ]);
    }

    public function adminDestroy(Reservation $reservation): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or SUPER_ADMIN can perform this action.',
            ], 403);
        }

        // Check if user owns the complex this reservation is for
        $terrain = Terrain::with('complexe')->findOrFail($reservation->terrain_id);
        if (! $user->isAdmin() && $terrain->complexe->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. This reservation is not in your complex.',
            ], 403);
        }

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
        ]);
    }

    public function archives(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        // 1. Terrain Reservations Archives
        $resQuery = Reservation::onlyTrashed()->with(['user:id,first_name,last_name,email', 'terrain.complexe']);
        if (! $user->isAdmin()) {
            $resQuery->whereHas('terrain.complexe', fn ($q) => $q->where('owner_id', $user->id));
        }
        $archivedReservations = $resQuery->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'type' => 'reservation_terrain',
                'type_label' => 'Réservation Court',
                'client_name' => $r->user ? ($r->user->first_name . ' ' . $r->user->last_name) : 'N/A',
                'client_email' => $r->user ? $r->user->email : 'N/A',
                'complexe_name' => $r->terrain?->complexe?->name ?? 'N/A',
                'item_detail' => $r->terrain?->name ?? 'N/A',
                'date' => $r->date_seance_res ?? ($r->start_at ? $r->start_at->format('Y-m-d') : 'N/A'),
                'montant' => $r->montant_res ?? 0,
                'statut_precedental' => $r->status,
                'statut_paiement' => $r->statut_paiement,
                'deleted_at' => $r->deleted_at?->toIso8601String(),
            ];
        });

        // 2. Activite Reservations Archives
        $actQuery = \App\Models\ReservationActivite::onlyTrashed()->with(['user:id,first_name,last_name,email', 'activite.complexe']);
        if (! $user->isAdmin()) {
            $actQuery->whereHas('activite.complexe', fn ($q) => $q->where('owner_id', $user->id));
        }
        $archivedActivites = $actQuery->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'type' => 'reservation_activite',
                'type_label' => 'Réservation Activité',
                'client_name' => $r->user ? ($r->user->first_name . ' ' . $r->user->last_name) : 'N/A',
                'client_email' => $r->user ? $r->user->email : 'N/A',
                'complexe_name' => $r->activite?->complexe?->name ?? 'N/A',
                'item_detail' => $r->activite?->nom ?? 'N/A',
                'date' => $r->date_seance ?? 'N/A',
                'montant' => $r->montant_paye ?? $r->activite?->prix ?? 0,
                'statut_precedental' => $r->statut,
                'statut_paiement' => $r->statut_paiement,
                'deleted_at' => $r->deleted_at?->toIso8601String(),
            ];
        });

        // 3. Abonnements Archives
        $abQuery = \App\Models\AbonnementAdherent::onlyTrashed()->with(['user:id,first_name,last_name,email', 'complexe', 'typeAbonnement']);
        if (! $user->isAdmin()) {
            $abQuery->whereHas('complexe', fn ($q) => $q->where('owner_id', $user->id));
        }
        $archivedAbonnements = $abQuery->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'type' => 'abonnement',
                'type_label' => 'Abonnement Client',
                'client_name' => $r->user ? ($r->user->first_name . ' ' . $r->user->last_name) : 'N/A',
                'client_email' => $r->user ? $r->user->email : 'N/A',
                'complexe_name' => $r->complexe?->name ?? 'N/A',
                'item_detail' => $r->typeAbonnement?->nom ?? 'Abonnement',
                'date' => $r->date_debut ? $r->date_debut->format('Y-m-d') : 'N/A',
                'montant' => $r->montant_apres_remise ?? $r->montant_vente ?? 0,
                'statut_precedental' => $r->statut,
                'statut_paiement' => $r->paye ? 'paye' : 'non_paye',
                'deleted_at' => $r->deleted_at?->toIso8601String(),
            ];
        });

        $allArchives = $archivedReservations->concat($archivedActivites)->concat($archivedAbonnements)->sortByDesc('deleted_at')->values();

        return response()->json([
            'success' => true,
            'data' => $allArchives,
        ]);
    }
}
