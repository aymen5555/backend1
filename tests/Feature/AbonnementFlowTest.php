<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Complexe;
use App\Models\Terrain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AbonnementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_cash_subscription_and_remains_client()
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Subscriber',
            'email' => 'client-sub@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $token = JWTAuth::fromUser($client);
        JWTAuth::setToken($token);
        $this->actingAs($client, 'api');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements', [
                'type' => 'MONTHLY',
                'payment_method' => 'especes',
                'price' => 49,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.payment_status', 'paid');

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements', ['user_id' => $client->id, 'status' => 'active', 'payment_status' => 'paid']);
    }

    public function test_client_can_create_card_subscription_and_confirm_payment()
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Card',
            'email' => 'client-card@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $token = JWTAuth::fromUser($client);
        JWTAuth::setToken($token);
        $this->actingAs($client, 'api');

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements', [
                'type' => 'YEARLY',
                'payment_method' => 'carte',
                'price' => 499,
            ]);

        $create->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'pending');

        $subscriptionId = $create->json('data.id');
        $this->assertNotNull($subscriptionId);

        $confirm = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/abonnements/{$subscriptionId}/confirm-payment", [
                'reference' => 'CARD-REF-123',
            ]);

        $confirm->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.reference', 'CARD-REF-123');

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements', ['id' => $subscriptionId, 'status' => 'active', 'payment_status' => 'paid']);
    }

    public function test_client_with_active_subscription_can_cancel_and_remains_client()
    {
        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Cancel',
            'email' => 'client-cancel@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $abonnement = Abonnement::create([
            'user_id' => $client->id,
            'type' => 'MONTHLY',
            'status' => 'active',
            'payment_method' => 'especes',
            'payment_status' => 'paid',
            'price' => 49,
            'start_at' => Carbon::now()->subDays(1),
            'expires_at' => Carbon::now()->addDays(29),
            'reference' => null,
        ]);

        $token = JWTAuth::fromUser($client);
        JWTAuth::setToken($token);
        $this->actingAs($client, 'api');

        $cancel = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/abonnements/{$abonnement->id}/cancel", []);

        $cancel->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'refunded');

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements', ['id' => $abonnement->id, 'status' => 'cancelled']);
    }
}
