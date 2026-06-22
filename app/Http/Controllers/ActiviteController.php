<?php

namespace App\Http\Controllers;

use App\Models\Activite;
use App\Models\Complexe;
use App\Models\ReservationActivite;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActiviteController extends Controller
{
    /* ─────────────────────────────────────────────────────
     | PUBLIC — no auth required
     ───────────────────────────────────────────────────── */

    /** GET /api/activites */
    public function index(Request $request): JsonResponse
    {
        // Public endpoint — always return ALL active activities (no auth-based filtering)
        $query = Activite::with('complexe')
            ->where('active', true);

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
            'data'    => $query->latest()->get(),
        ]);
    }

    /** GET /api/activites/{id} */
    public function show(Activite $activite): JsonResponse
    {
        if (!$activite->active) {
            return response()->json(['success' => false, 'message' => 'Activité introuvable.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $activite->load('complexe'),
        ]);
    }

    /** GET /api/activites/{id}/places?date=YYYY-MM-DD */
    public function places(Request $request, Activite $activite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:tomorrow',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Date invalide.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date = Carbon::parse($request->date)->toDateString();
        $dayMap = [
            1 => 'lundi', 2 => 'mardi', 3 => 'mercredi',
            4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 0 => 'dimanche',
        ];
        $day = $dayMap[Carbon::parse($date)->dayOfWeek] ?? '';

        if (!$activite->active || !in_array($day, $activite->jours ?? [], true)) {
            return response()->json([
                'success'           => true,
                'places_restantes'  => 0,
                'booked'            => null,
                'message'           => 'Cette activité n’a pas lieu ce jour-là.',
            ]);
        }

        $booked = ReservationActivite::where('activite_id', $activite->id)
            ->where('date_seance', $date)
            ->whereIn('statut', ['reservee', 'confirmee'])
            ->count();

        return response()->json([
            'success'          => true,
            'places_restantes' => max($activite->capacite - $booked, 0),
            'booked'           => $booked,
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

        if (!in_array($reservation->statut, ['reservee', 'confirmee'], true)) {
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

        $user = $reservation->user;
        $complexeId = $reservation->activite->complexe_id;
        $prixBase = $reservation->activite->prix ?? 0;
        $montant = $user->isAdherentAt($complexeId) ? round($prixBase * 0.80, 2) : $prixBase;

        $reservation->update([
            'statut'          => 'confirmee',
            'statut_paiement' => 'paye',
        ]);

        // We don't have ReglementReservation for activites, so just update status

        return response()->json([
            'success' => true,
            'message' => 'Paiement traité avec succès.',
            'data'    => $reservation->fresh()->load(['activite.complexe', 'user:id,first_name,last_name,email']),
        ]);
    }

    /** POST /api/activites/{id}/reserver */
    public function reserver(Request $request, Activite $activite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_seance'        => 'required|date|after:today',
            'modalite_paiement'  => 'required|in:especes,carte',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!$activite->active) {
            return response()->json([
                'success' => false,
                'message' => "Cette activité n'est plus disponible.",
            ], 422);
        }

        // Use a DB transaction and lock the activity row to prevent race conditions
        return DB::transaction(function () use ($activite, $request) {
            // Lock activite row for update to serialize concurrent reservations
            $locked = Activite::where('id', $activite->id)->lockForUpdate()->first();

            // Re-check capacity for that date under lock
            $booked = ReservationActivite::where('activite_id', $locked->id)
                ->where('date_seance', $request->date_seance)
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
                ->where('date_seance', $request->date_seance)
                ->whereIn('statut', ['reservee', 'confirmee'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous avez déjà réservé cette séance.',
                ], 409);
            }

            /** @var User $user */
            $user = auth('api')->user();
            $complexeId = $locked->complexe_id;

            // Adhérent discount: 20% off for active subscribers at this complexe
            $prixBase = $locked->prix ?? 0;
            $prix = $user->isAdherentAt($complexeId) ? round($prixBase * 0.80, 2) : $prixBase;

            $reservation = ReservationActivite::create([
                'activite_id'        => $locked->id,
                'user_id'            => auth('api')->id(),
                'date_seance'        => $request->date_seance,
                'statut'             => 'reservee',
                'statut_paiement'    => 'non_paye',
                'modalite_paiement'  => $request->modalite_paiement,
                'notes'              => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activité réservée avec succès.',
                'data'    => $reservation->load(['activite.complexe']),
                'montant_a_payer' => $prix,
            ], 201);
        });
    }

    /** GET /api/mes-activites */
    public function mesActivites(): JsonResponse
    {
        $reservations = ReservationActivite::with(['activite.complexe'])
            ->where('user_id', auth('api')->id())
            ->orderByDesc('date_seance')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $reservations,
        ]);
    }

    /** DELETE /api/activites/reservations/{id} */
    public function cancelMyReservation(ReservationActivite $reservation): JsonResponse
    {
        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if ($reservation->statut !== 'reservee') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations "réservée" peuvent être annulées.',
            ], 422);
        }

        // Must be more than 2 hours before the session
        $seanceAt = Carbon::parse($reservation->date_seance->format('Y-m-d') . ' ' . $reservation->activite->heure_debut);
        if (Carbon::now('Africa/Tunis')->diffInHours($seanceAt, false) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Annulation impossible moins de 2h avant la séance.',
            ], 422);
        }

        $reservation->update(['statut' => 'annulee']);

        return response()->json(['success' => true, 'message' => 'Réservation annulée.']);
    }

    public function deleteMyReservation(ReservationActivite $reservation): JsonResponse
    {
        if ($reservation->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Interdit.'], 403);
        }

        if ($reservation->statut !== 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les réservations annulées peuvent être supprimées.',
            ], 422);
        }

        $reservation->delete();

        return response()->json(['success' => true, 'message' => 'Réservation supprimée.']);
    }

    /* ─────────────────────────────────────────────────────
     | SUPER_ADMIN — protected + role:SUPER_ADMIN
     ───────────────────────────────────────────────────── */

    /** GET /api/admin/activites */
    public function adminIndex(): JsonResponse
    {
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant')
            ? Complexe::where('owner_id', $user->id)->pluck('id')
            : Complexe::pluck('id');

        $query = Activite::with('complexe')->whereIn('complexe_id', $myComplexeIds);

        $activites = $query->latest()->get();

        return response()->json(['success' => true, 'data' => $activites]);
    }

    /** POST /api/admin/activites */
    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'complexe_id'  => 'required|exists:complexes,id',
            'nom'          => 'required|string|max:255',
            'description'  => 'nullable|string',
            'sport'        => 'required|in:yoga,fitness,natation,musculation,football,padel,tennis,basketball,volleyball,handball',
            'niveau'       => 'required|in:debutant,intermediaire,expert,tous',
            'capacite'     => 'required|integer|min:1|max:100',
            'prix'         => 'required|numeric|min:0',
            'heure_debut'  => 'required|date_format:H:i',
            'heure_fin'    => 'required|date_format:H:i|after:heure_debut',
            'jours'        => 'required|array|min:1',
            'jours.*'      => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'image'        => 'nullable|string',
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

        if (!$complexe) {
            return response()->json(['success' => false, 'message' => 'Complexe non trouvé.'], 403);
        }

        $activite = Activite::create($validator->validated());

        return response()->json([
            'success' => true,
            'data'    => $activite->load('complexe'),
        ], 201);
    }

    /** PUT /api/admin/activites/{id} */
    public function update(Request $request, Activite $activite): JsonResponse
    {
        $this->authorizeAdmin($activite);

        $validator = Validator::make($request->all(), [
            'nom'         => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sport'       => 'sometimes|in:yoga,fitness,natation,musculation,football,padel,tennis,basketball,volleyball,handball',
            'niveau'      => 'sometimes|in:debutant,intermediaire,expert,tous',
            'capacite'    => 'sometimes|integer|min:1|max:100',
            'prix'        => 'sometimes|numeric|min:0',
            'heure_debut' => 'sometimes|date_format:H:i',
            'heure_fin'   => 'sometimes|date_format:H:i',
            'jours'       => 'sometimes|array|min:1',
            'jours.*'     => 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'image'       => 'nullable|string',
            'active'      => 'sometimes|boolean',
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
        $user = auth('api')->user();
        $myComplexeIds = ($user && $user->role === 'gerant') 
            ? Complexe::where('owner_id', $user->id)->pluck('id') 
            : Complexe::pluck('id');

        $query = ReservationActivite::with(['activite.complexe', 'user:id,first_name,last_name,email'])
            ->whereHas('activite', fn ($q) => $q->whereIn('complexe_id', $myComplexeIds));

        $reservations = $query->orderByDesc('date_seance')->get();

        return response()->json(['success' => true, 'data' => $reservations]);
    }

    /** PUT /api/admin/activites/reservations/{id}/confirm */
    public function confirmReservation(Request $request, ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);

        $validator = Validator::make($request->all(), [
            'modalite_paiement' => 'required|in:especes,carte',
            'statut_paiement'   => 'required|in:paye',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $reservation->update([
            'statut'            => 'confirmee',
            'statut_paiement'   => 'paye',
            'modalite_paiement' => $request->modalite_paiement,
        ]);

        return response()->json(['success' => true, 'data' => $reservation->fresh()->load(['activite.complexe', 'user:id,first_name,last_name,email'])]);
    }

    /** PUT /api/admin/activites/reservations/{id}/cancel */
    public function cancelReservation(ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);
        $reservation->update(['statut' => 'annulee']);

        return response()->json(['success' => true, 'message' => 'Réservation annulée.']);
    }

    /** DELETE /api/admin/activites/reservations/{id} */
    public function destroyReservation(ReservationActivite $reservation): JsonResponse
    {
        $this->authorizeAdminReservation($reservation);

        if ($reservation->statut !== 'annulee') {
            return response()->json([
                'success' => false,
                'message' => 'Only cancelled activity reservations can be deleted.',
            ], 422);
        }

        $reservation->delete();

        return response()->json(['success' => true, 'message' => 'Réservation supprimée.']);
    }

    /* ─────────────────────────────────────────────────────
     | Helpers
     ───────────────────────────────────────────────────── */

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
