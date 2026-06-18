<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;

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
}
