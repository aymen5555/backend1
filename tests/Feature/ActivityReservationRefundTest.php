<?php

namespace Tests\Feature;

use App\Http\Controllers\ActiviteController;
use App\Models\Activite;
use App\Models\Complexe;
use App\Models\ReservationActivite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActivityReservationRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_card_activity_reservation_cancel_marks_refund_pending_for_admin_confirmation(): void
    {
        $client = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Client',
            'email' => 'alice-activity@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
        ]);

        $activite = Activite::create([
            'complexe_id' => $complexe->id,
            'nom' => 'Yoga du matin',
            'description' => 'Séance test',
            'sport' => 'yoga',
            'niveau' => 'tous',
            'capacite' => 10,
            'prix' => 25.0,
            'heure_debut' => '09:00',
            'heure_fin' => '10:00',
            'jours' => ['lundi'],
            'active' => true,
        ]);

        $reservation = ReservationActivite::create([
            'activite_id' => $activite->id,
            'user_id' => $client->id,
            'date_seance' => now()->addDays(30)->toDateString(),
            'statut' => 'confirmee',
            'statut_paiement' => 'paye',
            'modalite_paiement' => 'carte',
            'montant_paye' => 25.0,
            'reference_paiement' => 'pi_test_123',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $token = JWTAuth::fromUser($client);
        $this->actingAs($client, 'api');
        JWTAuth::setToken($token);

        $controller = new class extends ActiviteController
        {
            public function __construct()
            {
            }
        };

        $response = $controller->cancelMyReservation($reservation);

        $this->assertSame(200, $response->getStatusCode());
        $reservation->refresh();
        $this->assertSame('annulee', $reservation->statut);
        $this->assertSame('paye', $reservation->statut_paiement);
        $this->assertSame('pending', $reservation->refund_status);
        $this->assertNull($reservation->refund_reference);
    }

    public function test_unpaid_card_activity_reservation_cancel_bypasses_time_limit(): void
    {
        $client = User::create([
            'first_name' => 'Bob',
            'last_name' => 'Client',
            'email' => 'bob-activity@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
        ]);

        $activite = Activite::create([
            'complexe_id' => $complexe->id,
            'nom' => 'Yoga du soir',
            'description' => 'Séance test',
            'sport' => 'yoga',
            'niveau' => 'tous',
            'capacite' => 10,
            'prix' => 25.0,
            'heure_debut' => '20:00',
            'heure_fin' => '21:00',
            'jours' => ['mardi'],
            'active' => true,
        ]);

        $reservation = ReservationActivite::create([
            'activite_id' => $activite->id,
            'user_id' => $client->id,
            'date_seance' => now()->addHour()->toDateString(),
            'statut' => 'reservee',
            'statut_paiement' => 'non_paye',
            'modalite_paiement' => 'carte',
            'montant_paye' => 25.0,
        ]);

        $token = JWTAuth::fromUser($client);
        $this->actingAs($client, 'api');
        JWTAuth::setToken($token);

        $controller = new class extends ActiviteController
        {
            public function __construct()
            {
            }
        };

        $response = $controller->cancelMyReservation($reservation);

        $this->assertSame(200, $response->getStatusCode());
        $reservation->refresh();
        $this->assertSame('annulee', $reservation->statut);
        $this->assertSame('non_paye', $reservation->statut_paiement);
        $this->assertSame('carte', $reservation->modalite_paiement);
    }
}
