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

class ReservationPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_pay_and_cancel_with_refund()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'One', 'email' => 'admin1@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'C1', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'T1', 'sport_type' => 'padel', 'price_per_hour' => 45, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'is_active' => true]);

        $client = User::create(['first_name' => 'Client', 'last_name' => 'One', 'email' => 'client1@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $token = JWTAuth::fromUser($client);
        JWTAuth::setToken($token);
        $this->actingAs($client, 'api');

        $start = Carbon::tomorrow()->setTime(10, 0, 0)->format('Y-m-d H:i:s');
        $end = Carbon::tomorrow()->setTime(11, 0, 0)->format('Y-m-d H:i:s');

        // create reservation (carte)
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $start,
                'end_at' => $end,
                'modalite_paiement' => 'carte',
            ]);

        $res->assertStatus(201)->assertJson(['success' => true]);
        $id = $res->json('data.id');

        // pay reservation
        JWTAuth::setToken($token);
        JWTAuth::shouldReceive('user')->andReturn($client);
        $pay = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/reservations/{$id}/pay", ['modalite' => 'carte', 'reference' => 'test_ref']);

        $pay->assertStatus(200)->assertJsonPath('data.statut_paiement', 'paye');

        // double pay should fail
        $double = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/reservations/{$id}/pay", ['modalite' => 'carte']);
        $double->assertStatus(422);

        // cancel (client) -> should create refund reglement
        $cancel = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/reservations/{$id}/cancel", []);
        $cancel->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('reglement_reservations', ['reservation_id' => $id, 'type' => 'remboursement']);
    }

    public function test_admin_can_confirm_cash_payment()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'Two', 'email' => 'admin2@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'C2', 'address' => 'Addr']);
        $terrain = Terrain::create(['complexe_id' => $complexe->id, 'name' => 'T2', 'sport_type' => 'tennis', 'price_per_hour' => 50, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'is_active' => true]);

        $client = User::create(['first_name' => 'Client', 'last_name' => 'Two', 'email' => 'client2@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $ctoken = JWTAuth::fromUser($client);
        JWTAuth::setToken($ctoken);
        $this->actingAs($client, 'api');

        $start = Carbon::tomorrow()->setTime(12, 0, 0)->format('Y-m-d H:i:s');
        $end = Carbon::tomorrow()->setTime(13, 0, 0)->format('Y-m-d H:i:s');

        // client creates cash reservation
        $create = $this->withHeader('Authorization', "Bearer {$ctoken}")
            ->postJson('/api/reservations', [
                'terrain_id' => $terrain->id,
                'start_at' => $start,
                'end_at' => $end,
                'modalite_paiement' => 'especes',
            ]);
        if ($create->status() !== 201) {
            file_put_contents(base_path('tests/_output/res_create_admin.json'), $create->getContent());
        }
        $create->assertStatus(201);
        $id = $create->json('data.id');

        // admin confirms cash
        $adminToken = JWTAuth::fromUser($admin);
        JWTAuth::setToken($adminToken);
        $this->actingAs($admin, 'api');
        $confirm = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->putJson("/api/admin/reservations/{$id}/confirm-cash", []);
        $confirm->assertStatus(200)->assertJsonPath('data.statut_paiement', 'paye');

        $this->assertDatabaseHas('reglement_reservations', ['reservation_id' => $id, 'type' => 'paiement', 'reference' => 'cash_confirmed_by_admin']);
    }
}
