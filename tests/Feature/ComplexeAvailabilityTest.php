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

class ComplexeAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_returns_slots_per_terrain()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'A', 'email' => 'adm@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'C-A', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'T-A', 'sport_type' => 'padel', 'price_per_hour' => 30, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '10:00:00', 'nbheures_seance' => 1, 'is_active' => true]);

        $date = Carbon::tomorrow()->format('Y-m-d');

        $token = JWTAuth::fromUser($admin);
        JWTAuth::setToken($token);
        $this->actingAs($admin, 'api');

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/complexes/{$complexe->id}/availability?date={$date}");

        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertArrayHasKey('terrains', $res->json('data'));
        $terrains = $res->json('data.terrains');
        $this->assertCount(1, $terrains);
        $this->assertArrayHasKey('slots', $terrains[0]);
    }

    public function test_conflicting_reservation_marks_slot_unavailable()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'B', 'email' => 'admb@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'C-B', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'T-B', 'sport_type' => 'tennis', 'price_per_hour' => 40, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '11:00:00', 'nbheures_seance' => 1, 'is_active' => true]);

        $date = Carbon::tomorrow()->format('Y-m-d');
        $start = Carbon::tomorrow()->setTime(9, 0, 0)->format('Y-m-d H:i:s');
        $end = Carbon::tomorrow()->setTime(10, 0, 0)->format('Y-m-d H:i:s');

        $client = User::create(['first_name' => 'Client', 'last_name' => 'C', 'email' => 'clientc@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $ctoken = JWTAuth::fromUser($client);
        JWTAuth::setToken($ctoken);
        $this->actingAs($client, 'api');

        // create reservation that conflicts with the 09:00 slot
        $create = $this->withHeader('Authorization', "Bearer {$ctoken}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $start,
                'end_at' => $end,
                'modalite_paiement' => 'especes',
            ]);

        $create->assertStatus(201);

        $adminToken = JWTAuth::fromUser($admin);
        JWTAuth::setToken($adminToken);
        $this->actingAs($admin, 'api');

        $res = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson("/api/complexes/{$complexe->id}/availability?date={$date}");

        $res->assertStatus(200)->assertJson(['success' => true]);
        $slots = $res->json('data.terrains.0.slots');

        // find 09:00 slot
        $found = array_filter($slots, fn ($s) => $s['time'] === '09:00');
        $this->assertNotEmpty($found);
        $slot = array_values($found)[0];
        $this->assertFalse($slot['available']);
    }
}
