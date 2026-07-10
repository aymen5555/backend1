<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use App\Models\ReservationActivite;
use App\Models\Activite;

class ReservationConflictService
{
    public function hasConflict(
        int $terrainId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeReservationId = null
    ): bool {
        if ($endAt->lte($startAt)) {
            return true;
        }

        // Block booking only if there's an overlapping active reservation (pending or confirmed)
        // Expired, cancelled, and played reservations don't block the slot
        $query = Reservation::query()
            ->where('terrain_id', $terrainId)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    /**
     * Check whether a given user has any reservation (court or activity)
     * that overlaps the given time range.
     */
    public function hasUserConflict(int $userId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): bool
    {
        // 1) Check court reservations for this user
        $q = Reservation::query()
            ->where('user_id', $userId)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeReservationId) {
            $q->where('id', '!=', $excludeReservationId);
        }

        if ($q->exists()) {
            return true;
        }

        // 2) Check activity reservations: map activity's date + heure_debut/heure_fin to a time range
        // Join ReservationActivite -> Activite and compare ranges
        $activityReservations = ReservationActivite::query()
            ->where('user_id', $userId)
            ->whereIn('statut', ['reservee', 'confirmee'])
            ->with('activite')
            ->get();

        foreach ($activityReservations as $ar) {
            $activite = $ar->activite;
            if (! $activite || ! $ar->date_seance) continue;

            // If activity doesn't have heure_debut/heure_fin, treat as full-day and consider any overlap on that date
            if (empty($activite->heure_debut) || empty($activite->heure_fin)) {
                $actStart = Carbon::parse($ar->date_seance)->startOfDay();
                $actEnd = Carbon::parse($ar->date_seance)->endOfDay();
            } else {
                $normalizedDate = Carbon::parse($ar->date_seance)->toDateString();
                $actStart = Carbon::parse("{$normalizedDate} {$activite->heure_debut}")->setTimezone(config('app.timezone'));
                $actEnd = Carbon::parse("{$normalizedDate} {$activite->heure_fin}")->setTimezone(config('app.timezone'));
            }

            if ($actEnd->gt($startAt) && $actStart->lt($endAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return detailed conflict entries for a terrain time range.
     * Each entry contains: type='reservation', id, start_at, end_at, status, user_id, terrain_id
     */
    public function getTerrainConflicts(int $terrainId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): array
    {
        $query = Reservation::query()
            ->where('terrain_id', $terrainId)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->get()->map(fn($r) => [
            'type' => 'reservation',
            'id' => $r->id,
            'user_id' => $r->user_id,
            'terrain_id' => $r->terrain_id,
            'start_at' => $r->start_at?->toDateTimeString(),
            'end_at' => $r->end_at?->toDateTimeString(),
            'status' => $r->status,
        ])->toArray();
    }

    /**
     * Return detailed conflict entries for a user's overlapping reservations (courts + activities).
     */
    public function getUserConflicts(int $userId, Carbon $startAt, Carbon $endAt, ?int $excludeReservationId = null): array
    {
        $out = [];

        $q = Reservation::query()
            ->where('user_id', $userId)
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($excludeReservationId) {
            $q->where('id', '!=', $excludeReservationId);
        }

        $out = array_merge($out, $q->get()->map(fn($r) => [
            'type' => 'reservation',
            'id' => $r->id,
            'user_id' => $r->user_id,
            'terrain_id' => $r->terrain_id,
            'start_at' => $r->start_at?->toDateTimeString(),
            'end_at' => $r->end_at?->toDateTimeString(),
            'status' => $r->status,
        ])->toArray());

        $activityReservations = ReservationActivite::query()
            ->where('user_id', $userId)
            ->whereIn('statut', ['reservee', 'confirmee'])
            ->with('activite')
            ->get();

        foreach ($activityReservations as $ar) {
            $activite = $ar->activite;
            if (! $activite || ! $ar->date_seance) continue;

            if (empty($activite->heure_debut) || empty($activite->heure_fin)) {
                $actStart = Carbon::parse($ar->date_seance)->startOfDay();
                $actEnd = Carbon::parse($ar->date_seance)->endOfDay();
            } else {
                $normalizedDate = Carbon::parse($ar->date_seance)->toDateString();
                $actStart = Carbon::parse("{$normalizedDate} {$activite->heure_debut}")->setTimezone(config('app.timezone'));
                $actEnd = Carbon::parse("{$normalizedDate} {$activite->heure_fin}")->setTimezone(config('app.timezone'));
            }

            if ($actEnd->gt($startAt) && $actStart->lt($endAt)) {
                $out[] = [
                    'type' => 'activity',
                    'id' => $ar->id,
                    'user_id' => $ar->user_id,
                    'activite_id' => $ar->activite_id,
                    'start_at' => $actStart->toDateTimeString(),
                    'end_at' => $actEnd->toDateTimeString(),
                    'statut' => $ar->statut,
                ];
            }
        }

        return $out;
    }
}
