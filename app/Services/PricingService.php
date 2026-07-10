<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use App\Models\Complexe;

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
        $minutes = $startAt->diffInMinutes($endAt);
        // Rounding policy: round to the nearest hour, halves (>=30min) round up.
        $hours = max(1, intdiv($minutes, 60));
        $remainder = $minutes % 60;
        if ($remainder >= 30) {
            $hours += 1;
        }
        $memberDiscount = Complexe::find($complexeId)?->member_discount_percentage;
        if ($client->isAdherentAt($complexeId)) {
            if ($memberDiscount !== null) {
                $multiplier = max(0, 1 - ($memberDiscount / 100));
            } else {
                $multiplier = 0.80; // default 20% member discount
            }
        } else {
            $multiplier = 1.0;
        }

        return round($pricePerHour * $hours * $multiplier, 2);
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
        $memberDiscount = Complexe::find($complexeId)?->member_discount_percentage;
        if ($client->isAdherentAt($complexeId)) {
            if ($memberDiscount !== null) {
                $multiplier = max(0, 1 - ($memberDiscount / 100));
            } else {
                $multiplier = 0.80;
            }
        } else {
            $multiplier = 1.0;
        }

        return round($prixBase * $multiplier, 2);
    }

    /**
     * Calculate subscription pricing based on plan discount.
     *
     * @param  \App\Models\TypeAbonnementAdherent  $type  The subscription plan
     * @param  float  $montantVente  Base sale amount (the plan's tarif)
     * @return array  Array with keys 'remise' and 'montant_apres_remise'
     */
    public function calculateAbonnementPricing(
        \App\Models\TypeAbonnementAdherent $type,
        float $montantVente
    ): array {
        $remise = (int) round($montantVente * ($type->discount_percentage ?? 0) / 100);

        return [
            'remise' => $remise,
            'montant_apres_remise' => $montantVente - $remise,
        ];
    }
}
