<?php

namespace Tests\Unit;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReservationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_expired_and_played_statuses()
    {
        // Create required related records: user, complexe, terrain
        $user = \App\Models\User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $complexe = \App\Models\Complexe::create([
            'owner_id' => $user->id,
            'name' => 'Demo Complexe',
            'address' => '123 Test St',
        ]);

        $terrain = \App\Models\Terrain::create([
            'complexe_id' => $complexe->id,
            'name' => 'Court 1',
            'sport_type' => 'tennis',
            'price_per_hour' => 20,
        ]);

        // Create a reserved reservation in the past
        $reserved = Reservation::create([
            'user_id' => $user->id,
            'terrain_id' => $terrain->id,
            'start_at' => Carbon::now()->subHours(3),
            'end_at' => Carbon::now()->subHours(2),
            'status' => 'pending',
            'type' => 'online',
            'modalite_paiement' => 'carte',
            'statut_paiement' => 'non_paye',
        ]);

        // Create a confirmed reservation in the past
        $confirmed = Reservation::create([
            'user_id' => $user->id,
            'terrain_id' => $terrain->id,
            'start_at' => Carbon::now()->subHours(5),
            'end_at' => Carbon::now()->subHours(4),
            'status' => 'confirmed',
            'type' => 'online',
            'modalite_paiement' => 'especes',
            'statut_paiement' => 'paye',
            'montant_paye' => 30,
        ]);

        // Run the updater
        Reservation::updateExpiredStatus();

        $reserved->refresh();
        $confirmed->refresh();

        $this->assertEquals('expired', $reserved->status);
        $this->assertEquals('played', $confirmed->status);
    }
}
