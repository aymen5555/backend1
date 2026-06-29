<?php

namespace Tests\Feature;

use App\Models\Complexe;
use App\Models\Terrain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TimezoneSlotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_terrain_slots_include_timezone_and_iso_datetimes()
    {
        $super = User::create(['first_name' => 'TZ', 'last_name' => 'Admin', 'email' => 'tzadmin@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $super->id, 'name' => 'TZ Complexe', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'TZ Terrain', 'sport_type' => 'padel', 'price_per_hour' => 50, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '10:00:00', 'nbheures_seance' => 1, 'is_active' => true]);

        $token = JWTAuth::fromUser($super);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/terrains/{$terrain->id}/slots?date={$date}&timezone=Europe%2FParis");

        $res->assertStatus(200)
            ->assertJsonPath('data.timezone', 'Europe/Paris')
            ->assertJsonPath('data.terrain_id', $terrain->id);

        $slot = $res->json('data.slots.0');
        $this->assertArrayHasKey('starts_at', $slot);
        $this->assertArrayHasKey('ends_at', $slot);
        $this->assertSame('Europe/Paris', $slot['timezone']);
    }

    public function test_booking_a_slot_using_timezone_aware_iso_datetimes_succeeds()
    {
        $super = User::create(['first_name' => 'TZ', 'last_name' => 'Admin', 'email' => 'tzadmin2@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $super->id, 'name' => 'TZ Complexe 2', 'address' => 'Addr']);
        $terrain = Terrain::create([
            'complexe_id' => $complexe->id,
            'name' => 'TZ Terrain 2',
            'sport_type' => 'padel',
            'price_per_hour' => 55,
            'heure_ouverture' => '08:00:00',
            'heure_fermeture' => '12:00:00',
            'nbheures_seance' => 1,
            'is_active' => true,
        ]);

        $client = User::create(['first_name' => 'Client', 'last_name' => 'TZ', 'email' => 'clienttz@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $token = JWTAuth::fromUser($client);

        $date = Carbon::tomorrow()->format('Y-m-d');
        $slotsResponse = $this->getJson("/api/terrains/{$terrain->id}/slots?date={$date}&timezone=Europe%2FParis");

        $slotsResponse->assertStatus(200)
            ->assertJsonPath('data.timezone', 'Europe/Paris')
            ->assertJsonPath('data.terrain_id', $terrain->id);

        $slot = $slotsResponse->json('data.slots.0');
        $this->assertArrayHasKey('starts_at', $slot);
        $this->assertArrayHasKey('ends_at', $slot);
        $this->assertSame('Europe/Paris', $slot['timezone']);

        $reservationResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $slot['starts_at'],
                'end_at' => $slot['ends_at'],
                'modalite_paiement' => 'carte',
            ]);

        $reservationResponse->assertStatus(201)
            ->assertJsonPath('data.terrain_id', $terrain->id);

        $returnedStart = $reservationResponse->json('data.start_at');
        $returnedEnd = $reservationResponse->json('data.end_at');

        $this->assertSame(
            Carbon::parse($slot['starts_at'])->getTimestamp(),
            Carbon::parse($returnedStart)->getTimestamp(),
            'Reservation start time should represent the same instant as the slot start.'
        );
        $this->assertSame(
            Carbon::parse($slot['ends_at'])->getTimestamp(),
            Carbon::parse($returnedEnd)->getTimestamp(),
            'Reservation end time should represent the same instant as the slot end.'
        );

        $this->assertDatabaseHas('reservations', [
            'terrain_id' => $terrain->id,
            'status' => 'pending',
        ]);
    }
}
