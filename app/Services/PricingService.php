<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

/**
 * Centralised pricing calculator.
 *
 * Usage:
 *   app(PricingService::class)->calculate($pricePerHour, $startAt, $endAt, $client, $complexeId);
 *
 * For activities (flat rate, not hourly):
 *   app(PricingService::class)->calculateFlat($prixBase, $client, $complexeId);
 */
class PricingService
{
    /**
     * Calculate court/reservation price based on duration + adherent discount.
     *
     * @param  float  $pricePerHour  Court's hourly rate
     * @param  Carbon  $startAt  Reservation start datetime
     * @param  Carbon  $endAt  Reservation end datetime
     * @param  User  $client  The BOOKING CLIENT (never the admin actor)
     * @param  int  $complexeId  Complexe to check adherent membership against
     */
    public function calculate(
        float $pricePerHour,
        Carbon $startAt,
        Carbon $endAt,
        User $client,
        int $complexeId
    ): float {
        $hours = $startAt->diffInMinutes($endAt) / 60;
        $hours = max(1, round($hours, 2));
        $discount = $client->isAdherentAt($complexeId) ? 0.80 : 1.0;

        return round($pricePerHour * $hours * $discount, 2);
    }

    /**
     * Calculate flat-rate price (e.g. activity session) with adherent discount.
     *
     * @param  float  $prixBase  Base session price
     * @param  User  $client  The booking client
     * @param  int  $complexeId  Complexe to check adherent membership against
     */
    public function calculateFlat(
        float $prixBase,
        User $client,
        int $complexeId
    ): float {
        $discount = $client->isAdherentAt($complexeId) ? 0.80 : 1.0;

        return round($prixBase * $discount, 2);
    }
}
