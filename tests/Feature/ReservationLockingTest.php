<?php

namespace Tests\Feature;

use App\Models\Complexe;
use App\Models\Reservation;
use App\Models\Terrain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservationLockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_booking_is_blocked_under_terrain_lock()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'Lock', 'email' => 'lock@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'LockC', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'T-Lock', 'sport_type' => 'padel', 'price_per_hour' => 40, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'is_active' => true]);

        $clientA = User::create(['first_name' => 'Client', 'last_name' => 'A', 'email' => 'clientA@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $clientB = User::create(['first_name' => 'Client', 'last_name' => 'B', 'email' => 'clientB@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);

        $tokenA = JWTAuth::fromUser($clientA);
        $tokenB = JWTAuth::fromUser($clientB);

        $date = Carbon::tomorrow()->format('Y-m-d H:i:s');
        $start = Carbon::tomorrow()->setTime(10, 0, 0)->format('Y-m-d H:i:s');
        $end = Carbon::tomorrow()->setTime(11, 0, 0)->format('Y-m-d H:i:s');

        $resA = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $start,
                'end_at' => $end,
                'modalite_paiement' => 'carte',
            ]);

        $resA->assertStatus(201);

        $resB = $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $start,
                'end_at' => $end,
                'modalite_paiement' => 'carte',
            ]);

        $resB->assertStatus(409);
        $this->assertEquals(1, Reservation::where('terrain_id', $terrain->id)->count());
    }
}
