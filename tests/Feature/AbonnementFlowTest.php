<?php

namespace Tests\Feature;

use App\Models\AbonnementAdherent;
use App\Models\Complexe;
use App\Models\TypeAbonnementAdherent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AbonnementFlowTest extends TestCase
{
    use RefreshDatabase;

    private function setupData()
    {
        $admin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin-abon@example.test',
            'password' => bcrypt('secret'),
            'role' => 'super_admin',
        ]);

        $complexe = Complexe::create([
            'owner_id' => $admin->id,
            'name' => 'Abon Complexe',
            'address' => 'Addr',
        ]);

        $type = TypeAbonnementAdherent::create([
            'complexe_id' => $complexe->id,
            'nom' => 'Test Formula',
            'description' => 'A description',
            'nb_mois' => 1,
            'tarif' => 120.0,
            'prix_unitaire' => 120.0,
            'niveau_sportif_cible' => 'tous',
            'sport_cible' => 'padel',
            'active' => true,
        ]);

        return [$admin, $complexe, $type];
    }

    public function test_client_can_create_cash_subscription_and_remains_client()
    {
        list($admin, $complexe, $type) = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Subscriber',
            'email' => 'client-sub@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $token = JWTAuth::fromUser($client);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements/souscrire', [
                'type_abonnement_id' => $type->id,
                'modalite_paiement' => 'especes',
                'date_debut' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.statut', 'actif')
            ->assertJsonPath('data.paye', false);

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements_adherent', [
            'user_id' => $client->id,
            'statut' => 'actif',
            'paye' => false,
        ]);
    }

    public function test_client_can_create_card_subscription_and_confirm_payment()
    {
        list($admin, $complexe, $type) = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Card',
            'email' => 'client-card@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $token = JWTAuth::fromUser($client);

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements/souscrire', [
                'type_abonnement_id' => $type->id,
                'modalite_paiement' => 'carte',
                'date_debut' => now()->toDateString(),
            ]);

        $create->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.statut', 'actif');

        $subscriptionId = $create->json('data.id');
        $this->assertNotNull($subscriptionId);

        $confirm = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/abonnement-adherents/{$subscriptionId}/pay", [
                'modalite_paiement' => 'carte',
                'reference' => 'TXN-1234-567',
            ]);

        $confirm->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.statut', 'actif')
            ->assertJsonPath('data.paye', true);

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements_adherent', [
            'id' => $subscriptionId,
            'statut' => 'actif',
            'paye' => true,
        ]);
        $this->assertDatabaseHas('reglements_abonnement', [
            'abonnement_id' => $subscriptionId,
            'modalite' => 'carte',
            'reference' => 'TXN-1234-567',
        ]);
    }

    public function test_client_with_active_subscription_can_cancel_and_remains_client()
    {
        list($admin, $complexe, $type) = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Cancel',
            'email' => 'client-cancel@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $abonnement = AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant_vente' => 120.0,
            'remise' => 0,
            'montant_apres_remise' => 120.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        $token = JWTAuth::fromUser($client);

        $cancel = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/abonnement-adherents/{$abonnement->id}/cancel", []);

        $cancel->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Abonnement annulé.']);

        $client->refresh();
        $this->assertSame('client', $client->role);
        $this->assertDatabaseHas('abonnements_adherent', [
            'id' => $abonnement->id,
            'statut' => 'annule',
        ]);
    }
}
