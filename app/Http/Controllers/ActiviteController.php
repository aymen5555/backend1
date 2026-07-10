<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\Complexe;
use App\Models\ReservationActivite;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PricingService;
use App\Services\ReservationConflictService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Refund;
use Stripe\Stripe;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActiviteController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly ReservationConflictService $conflicts
    ) {
    }
    /* ─────────────────────────────────────────────────────
     | PUBLIC — no auth required
     ───────────────────────────────────────────────────── */

    /** GET /api/activites */
    public function index(Request $request): JsonResponse
    {
        // Public endpoint — always return active activities that belong to a complexe.
        $query = Activite::with('complexe')
            ->where('active', true)
            ->whereNotNull('complexe_id')
            ->whereHas('complexe');

        if ($request->filled('complexe_id')) {
            $query->where('complexe_id', $request->complexe_id);
        }
        if ($request->filled('sport')) {
            $query->where('sport', $request->sport);
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->get(),
        ]);
    }

    /** GET /api/activites/{id} */
    public function show(Activite $activite): JsonResponse
    {
        if (! $activite->active || ! $activite->complexe()->exists()) {
            return response()->json(['success' => false, 'message' => 'Activité introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $activite->load('complexe'),
        ]);
    }

    /** GET /api/activites/{id}/places?date=YYYY-MM-DD */
    public function places(Request $request, Activite $activite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Date invalide.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $this->normalizeDate($request->date);
        $dayMap = [
            1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
            4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 0 => 'dimanche',
        ];
        $day = $dayMap[Carbon::parse($date)->dayOfWeek] ?? '';

        if (! $activite->active || ! in_array($day, $activite->jours ?? [], true)) {
            return response()->json([
                'success' => true,
                'places_restantes' => 0,
                'booked' => null,
                'message' => 'Cette activité n’a pas lieu ce jour-là.',
            ]);
        }

        $booked = ReservationActivite::where('activite_id', $activite->id)
            ->where('date_seance', $date)
            ->whereIn('statut', ['reservee', 'confirmee'])
            ->count();

        $userConflict = false;
        $user = auth('api')->user();
        if ($user) {
            if (empty($activite->heure_debut) || empty($activite->heure_fin)) {
                $actStart = Carbon::parse($date)->startOfDay();
                $actEnd = Carbon::parse($date)->endOfDay();
            } else {
                $normalizedDate = $this->normalizeDate($date);
                $actStart = Carbon::parse("{$normalizedDate} {$activite->heure_debut}")->setTimezone(config('app.timezone'));
                $actEnd = Carbon::parse("{$normalizedDate} {$activite->heure_fin}")->setTimezone(config('app.timezone'));
            }

            $userConflict = $this->conflicts->hasUserConflict($user->id, $actStart, $actEnd);
        }

        return response()->json([
            'success' => true,
            'places_restantes' => max($activite->capacite - $booked, 0),
            'booked' => $booked,
            'user_conflict' => $userConflict,
        ]);
    }

    /* ─────────────────────────────────────────────────────
     | CLIENT — protected
     ───────────────────────────────────────────────────── */

    /** PUT /api/activites/reservations/{id}/pay */
    public function payReservation(Request $request, ReservationActivite $reservation): JsonResponse
    {
        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if ($reservation->statut_paiement === 'paye' || $reservation->statut_paiement === 'rembourse') {
            return response()->json([
                'success' => false,
                'message' => 'Action impossible sur cette réservation',
            ], 422);
        }

        if (! in_array($reservation->statut, ['reservee', 'confirmee'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations en attente peuvent être payées',
            ], 422);
        }

        if ($reservation->modalite_paiement !== 'carte') {
            return response()->json([
                'success' => false,
                'message' => 'Cette réservation n\'admet pas de paiement en ligne',
            ], 422);
        }

        $client = User::findOrFail($reservation->user_id);
        $complexeId = $reservation->activite->complexe_id;
        $prixBase = $reservation->activite->prix ?? 0;
        $montant = $this->pricing->calculateFlat($prixBase, $client, $complexeId);

        $paymentIntentId = $request->input('payment_intent_id') ?? $request->input('reference') ?? null;

        $reservation->update([
            'statut' => 'confirmee',
            'statut_paiement' => 'paye',
            'montant_paye' => $montant,
            'modalite_paiement' => $request->modalite_paiement ?? $reservation->modalite_paiement,
            'reference_paiement' => $paymentIntentId ?? null,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        // We don't have ReglementReservation for activites, so just update status

        return response()->json([
            'success' => true,
            'message' => 'Paiement traité avec succès.',
            'data' => $reservation->fresh()->load(['activite.complexe', 'user:id,first_name,last_name,email']),
        ]);
    }

    /** POST /api/activites/{id}/reserver */
    public function reserver(Request $request, Activite $activite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_seance' => 'required|date|after:today',
            'modalite_paiement' => 'required|in:especes,carte',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $activite->active || ! $activite->complexe()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette activité n’est pas disponible.',
            ], 404);
        }

        // Normalize incoming date to Y-m-d to avoid double-time concatenation bugs
        $date = $this->normalizeDate($request->date_seance);

        // Validate that the requested date falls on a day this activity runs
        $dayMap = [
            0 => 'dimanche', 1 => 'lundi', 2 => 'mardi',
            3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi',
        ];
        $day = $dayMap[Carbon::parse($date)->dayOfWeek] ?? '';
        if (! in_array($day, $activite->jours ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => "Cette activité n'a pas lieu ce jour-là.",
            ], 422);
        }

        // Use a DB transaction and lock the activity row to prevent race conditions
        return DB::transaction(function () use ($activite, $request, $date) {
            // Lock activite row for update to serialize concurrent reservations
            $locked = Activite::where('id', $activite->id)->lockForUpdate()->first();

            // Re-check capacity for that date under lock
            $booked = ReservationActivite::where('activite_id', $locked->id)
                ->where('date_seance', $date)
                ->whereIn('statut', ['reservee', 'confirmee'])
                ->count();

            if ($booked >= $locked->capacite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette séance est complète pour la date choisie.',
                ], 422);
            }

            // Prevent duplicate booking for same user/activite/date (re-check under lock)
            $existing = ReservationActivite::where('activite_id', $locked->id)
                ->where('user_id', auth('api')->id())
                ->where('date_seance', $date)
                ->whereIn('statut', ['reservee', 'confirmee'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà réservé cette séance.',
                ], 409);
            }

            // Prevent overlapping reservations for the same user across courts/activities
            $user = auth('api')->user();
            if ($user) {
                // Build activity time range
                if (empty($locked->heure_debut) || empty($locked->heure_fin)) {
                    $actStart = Carbon::parse($date)->startOfDay();
                    $actEnd = Carbon::parse($date)->endOfDay();
                } else {
                    // Use only the Y-m-d date part when concatenating with heure_debut/heure_fin
                    $normalizedDate = $this->normalizeDate($date);
                    $actStart = Carbon::parse("{$normalizedDate} {$locked->heure_debut}")->setTimezone(config('app.timezone'));
                    $actEnd = Carbon::parse("{$normalizedDate} {$locked->heure_fin}")->setTimezone(config('app.timezone'));
                }

                if ($this->conflicts->hasUserConflict($user->id, $actStart, $actEnd)) {
                    $conflicts = $this->conflicts->getUserConflicts($user->id, $actStart, $actEnd);
                    return response()->json([
                        'success' => false,
                        'error' => 'conflict',
                        'type' => 'user_overlap',
                        'message' => 'Vous avez déjà une réservation à ce créneau horaire.',
                        'conflicts' => $conflicts,
                    ], 409);
                }
            }

            /** @var User $user */
            $user = auth('api')->user();
            $complexeId = $locked->complexe_id;

            // Adhérent discount via PricingService
            $prixBase = $locked->prix ?? 0;
            $prix = $this->pricing->calculateFlat($prixBase, $user, $complexeId);

            $reservation = ReservationActivite::create([
                'activite_id' => $locked->id,
                'user_id' => auth('api')->id(),
                'date_seance' => $date,
                'statut' => 'reservee',
                'statut_paiement' => 'non_paye',
                'modalite_paiement' => $request->modalite_paiement,
                'notes' => $request->notes,
                'montant_paye' => $prix,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activité réservée avec succès.',
                'data' => $reservation->load(['activite.complexe']),
                'montant_a_payer' => $prix,
            ], 201);
        });
    }

    private function normalizeDate(string $rawDate): string
    {
        return Carbon::parse($rawDate)->toDateString();
    }

    public function mesActivites(): JsonResponse
    {
        ReservationActivite::updateExpiredStatus();

        $reservations = ReservationActivite::with(['activite.complexe'])
            ->where('user_id', auth('api')->id())
            ->whereHas('activite', fn ($q) => $q->whereNotNull('complexe_id')->whereHas('complexe'))
            ->orderByDesc('date_seance')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    /** DELETE /api/activites/reservations/{id} */
    public function cancelMyReservation(ReservationActivite $reservation): JsonResponse
    {
        $user = auth('api')->user();

        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        // Bypass 2-hour rule for unpaid card reservations (user aborted payment)
        if ($reservation->modalite_paiement === 'carte' && $reservation->statut_paiement === 'non_paye') {
            $reservation->update(['statut' => 'annulee']);
            AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Client cancelled unpaid card activity reservation');
            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée — paiement non effectué.',
            ]);
        }

        if (! in_array($reservation->statut, ['reservee', 'confirmee'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations "réservée" ou "confirmée" peuvent être annulées.',
            ], 422);
        }

        // Must be more than 2 hours before the session
        $seanceAt = Carbon::parse($reservation->date_seance->format('Y-m-d') . ' ' . $reservation->activite->heure_debut);
        $now = Carbon::now('Africa/Tunis');
        $isPaidCardReservation = $reservation->statut_paiement === 'paye' && $reservation->modalite_paiement === 'carte';

        if (! $isPaidCardReservation && $now->gte($seanceAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Annulation impossible après le début de la séance.',
            ], 422);
        }

        if (! $isPaidCardReservation && $seanceAt->diffInHours($now) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Annulation impossible moins de 2h avant la séance.',
            ], 422);
        }

        $refundStatus = 'not_requested';
        $isPaidCardReservation = $reservation->statut_paiement === 'paye' && $reservation->modalite_paiement === 'carte';
        if ($isPaidCardReservation) {
            $refundStatus = 'pending';
        }

        $reservation->update([
            'statut' => 'annulee',
            'refund_status' => $refundStatus,
            'refund_reference' => null,
        ]);

        AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Client cancelled activity reservation');

        return response()->json([
            'success' => true,
            'message' => $refundStatus === 'not_requested' ? 'Réservation annulée.' : 'Réservation annulée. Remboursement initié.',
        ]);
    }

    public function confirmerRemboursement(ReservationActivite $reservation): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if (! $user->isAdmin() && ! ($user->isGerant() && $reservation->activite?->complexe && $reservation->activite->complexe->owner_id === $user->id)) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if ($reservation->refund_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Aucun remboursement en attente.'], 422);
        }

        return DB::transaction(function () use ($reservation, $user): JsonResponse {
            if (! $reservation->stripe_payment_intent_id) {
                $reservation->update([
                    'refund_status' => 'succeeded',
                    'refund_reference' => 'manual',
                    'statut_paiement' => 'rembourse',
                ]);

                AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => 'succeeded', 'method' => 'manual']);

                return response()->json(['success' => true, 'message' => 'Remboursement validé.']);
            }

            try {
                $refundResult = $this->createStripeRefund($reservation->stripe_payment_intent_id, $reservation);
                $refundStatus = $refundResult['status'] === 'succeeded' ? 'succeeded' : 'failed';
                $reservation->update([
                    'refund_status' => $refundStatus,
                    'refund_reference' => $refundResult['id'] ?? null,
                    'statut_paiement' => $refundStatus === 'succeeded' ? 'rembourse' : $reservation->statut_paiement,
                ]);

                AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => $refundStatus, 'reference' => $refundResult['id'] ?? null, 'method' => 'stripe']);
            } catch (\Throwable $e) {
                $reservation->update([
                    'refund_status' => 'failed',
                    'refund_reference' => null,
                ]);

                AuditService::refund($user, 'ReservationActivite', $reservation->id, ['status' => 'failed', 'error' => $e->getMessage()]);
            }

            return response()->json(['success' => true, 'message' => 'Remboursement validé.']);
        });
    }

    public function deleteMyReservation(ReservationActivite $reservation): JsonResponse
    {
        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if ($reservation->statut !== 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations annulées peuvent être conservées.',
            ], 422);
        }

        $reservation->update(['statut' => 'annulee']);

        return response()->json(['success' => true, 'message' => 'Réservation conservée et marquée comme annulée.']);
    }

    /* ─────────────────────────────────────────────────────
     | SUPER_ADMIN — protected + role:SUPER_ADMIN
     ───────────────────────────────────────────────────── */

    /** GET /api/admin/activites */
    public function adminIndex(): JsonResponse
    {
        $user = auth('api')->user();
        $query = Activite::with('complexe')
            ->whereNotNull('complexe_id')
            ->whereHas('complexe');

        if ($user && $user->role === 'gerant') {
            $myComplexeIds = Complexe::where('owner_id', $user->id)->pluck('id');
            $query->whereIn('complexe_id', $myComplexeIds);
        }

        $activites = $query->latest()->get();

        return response()->json(['success' => true, 'data' => $activites]);
    }

    /** POST /api/admin/activites */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'complexe_id' => 'required|exists:complexes,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sport' => 'required|in:yoga,fitness,natation,musculation,football,padel,tennis,basketball,volleyball,handball',
            'niveau' => 'required|in:debutant,intermediaire,expert,tous',
            'capacite' => 'required|integer|min:1|max:100',
            'prix' => 'required|numeric|min:0',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'jours' => 'required|array|min:1',
            'jours.*' => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Make sure the complexe belongs to the admin (super_admin can create for any complex)
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $complexe = Complexe::whereIn('id', $myComplexeIds)->where('id', $request->complexe_id)->first();

        if (! $complexe) {
            return response()->json(['success' => false, 'message' => 'Complexe non trouvé.'], 403);
        }

        $activite = Activite::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $activite->load('complexe'),
        ], 201);
    }

    /** PUT /api/admin/activites/{id} */
    public function update(Request $request, Activite $activite): JsonResponse
    {
        $this->authorizeAdmin($activite);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sport' => 'sometimes|in:yoga,fitness,natation,musculation,football,padel,tennis,basketball,volleyball,handball',
            'niveau' => 'sometimes|in:debutant,intermediaire,expert,tous',
            'capacite' => 'sometimes|integer|min:1|max:100',
            'prix' => 'sometimes|numeric|min:0',
            'heure_debut' => 'sometimes|date_format:H:i',
            'heure_fin' => 'sometimes|date_format:H:i',
            'jours' => 'sometimes|array|min:1',
            'jours.*' => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'image' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $activite->update($validator->validated());

        return response()->json(['success' => true, 'data' => $activite->fresh()->load('complexe')]);
    }

    /** DELETE /api/admin/activites/{id} */
    public function destroy(Activite $activite): JsonResponse
    {
        $this->authorizeAdmin($activite);
        // Soft-delete: just deactivate to preserve historical reservations
        $activite->update(['active' => false]);

        return response()->json(['success' => true, 'message' => 'Activité désactivée.']);
    }

    /** GET /api/admin/activites/reservations */
    public function adminReservations(): JsonResponse
    {
        ReservationActivite::updateExpiredStatus();

        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = ReservationActivite::with(['activite.complexe', 'user:id,first_name,last_name,email'])
            ->whereHas('activite', fn ($q) => $q->whereIn('complexe_id', $myComplexeIds)
                ->whereNotNull('complexe_id')
                ->whereHas('complexe'));

        $reservations = $query->orderByDesc('date_seance')->get();

        return response()->json(['success' => true, 'data' => $reservations]);
    }

    /** api/admin/activites/reservations/{id}/confirm */
    public function confirmReservation(Request $request, ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);

        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            'statut_paiement' => 'required|in:paye',
            'reference' => 'nullable|string|max:100',
            'montant' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $reservation->update([
            'statut' => 'confirmee',
            'statut_paiement' => 'paye',
            'modalite_paiement' => $request->modalite_paiement,
            'reference_paiement' => $request->reference ?? $reservation->reference_paiement,
            'montant_paye' => $request->montant ?? $reservation->montant_paye,
        ]);

        AuditService::payment($user, 'ReservationActivite', $reservation->id, (float) ($request->montant ?? $reservation->montant_paye), $request->modalite_paiement);

        return response()->json(['success' => true, 'data' => $reservation->fresh()->load(['activite.complexe', 'user:id,first_name,last_name,email'])]);
    }

    /** PUT /api/admin/activites/reservations/{id}/cancel */
    public function cancelReservation(ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);

        $user = auth('api')->user();

        $refundStatus = 'not_requested';
        if ($reservation->statut_paiement === 'paye' && $reservation->modalite_paiement === 'carte') {
            $refundStatus = 'pending';
        }

        $reservation->update([
            'statut' => 'annulee',
            'refund_status' => $refundStatus,
            'refund_reference' => null,
        ]);

        AuditService::cancel($user, 'ReservationActivite', $reservation->id, 'Admin cancelled activity reservation');

        return response()->json([
            'success' => true,
            'message' => $refundStatus === 'pending'
                ? 'Réservation annulée. Un remboursement sera traité par le gérant.'
                : 'Réservation annulée.',
        ]);
    }

    /** DELETE /api/admin/activites/reservations/{id} */
    public function destroyReservation(ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);

        if ($reservation->statut !== 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Only cancelled activity reservations can be archived.',
            ], 422);
        }

        // Always soft-delete to preserve audit trail — appears in archives
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Réservation d\'activité archivée (supprimée de l\'affichage).',
        ]);
    }

    /* ─────────────────────────────────────────────────────
     | Helpers
     ───────────────────────────────────────────────────── */

    protected function createStripeRefund(string $paymentIntentId, ReservationActivite $reservation): array
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $refund = Refund::create([
            'payment_intent' => $paymentIntentId,
            'metadata' => [
                'reservation_activite_id' => $reservation->id,
                'type' => 'activite',
            ],
        ]);

        return [
            'id' => $refund->id,
            'status' => $refund->status,
        ];
    }

    private function authorizeAdmin(Activite $activite): void
    {
        $user = JWTAuth::user();
        // Super admin can access any activity
        if ($user->isAdmin()) {
            return;
        }
        if ($activite->complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Interdit.'], 403));
        }
    }

    private function authorizeAdminReservation(ReservationActivite $reservation): void
    {
        $user = JWTAuth::user();
        // Super admin can access any reservation
        if ($user->isAdmin()) {
            return;
        }
        if ($reservation->activite->complexe->owner_id !== $user->id) {
            abort(response()->json(['success' => false, 'message' => 'Interdit.'], 403));
        }
    }
}
