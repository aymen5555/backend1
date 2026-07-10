<?php

namespace Tests\Unit;

use App\Services\PricingService;
use Tests\TestCase;
use Carbon\Carbon;

class PricingServiceTest extends TestCase
{
    public function test_calculate_flat_applies_adherent_discount()
    {
        $service = new PricingService();

        // Create a User subclass that returns true for isAdherentAt
        $user = new class extends \App\Models\User {
            public function isAdherentAt(int $complexeId): bool
            {
                return true;
            }
        };

        $prixBase = 100.0;
        $montant = $service->calculateFlat($prixBase, $user, 1);

        // adherent discount is 0.80 -> 100 * 0.8 = 80.00
        $this->assertEquals(80.00, $montant);
    }

    public function test_calculate_abonnement_pricing_applies_plan_discount()
    {
        $service = new PricingService();

        $type = new class extends \App\Models\TypeAbonnementAdherent {
            public $discount_percentage = 25; // 25% discount
        };

        $montantVente = 200.0;
        $result = $service->calculateAbonnementPricing($type, $montantVente);

        // 25% of 200 = 50
        $this->assertEquals(50, $result['remise']);
        $this->assertEquals(150.0, $result['montant_apres_remise']);
    }

    public function test_rounding_policy_1h30_rounds_up()
    {
        $service = new PricingService();

        $user = new class extends \App\Models\User {
            public function isAdherentAt(int $complexeId): bool
            {
                return false;
            }
        };

        $start = Carbon::parse('2026-07-10 10:00');
        $end = Carbon::parse('2026-07-10 11:30'); // 1h30 should round up to 2h
        $amount = $service->calculate(50.0, $start, $end, $user, 1);

        $this->assertEquals(100.00, $amount);
    }

    public function test_rounding_policy_less_than_1h30_stays_one_hour()
    {
        $service = new PricingService();

        $user = new class extends \App\Models\User {
            public function isAdherentAt(int $complexeId): bool
            {
                return false;
            }
        };

        $start = Carbon::parse('2026-07-10 10:00');
        $end = Carbon::parse('2026-07-10 11:29'); // 1h29 should be 1h
        $amount = $service->calculate(50.0, $start, $end, $user, 1);

        $this->assertEquals(50.00, $amount);
    }
}
