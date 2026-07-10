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
            'discount_percentage' => 0,
            'active' => true,
        ]);

        return [$admin, $complexe, $type];
    }

    public function test_legacy_active_subscription_is_treated_as_adherent()
    {
        [$admin, $complexe, $type] = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Legacy',
            'email' => 'client-legacy@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant_vente' => 100.0,
            'remise' => 0,
            'montant_apres_remise' => 100.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        $subscription = AbonnementAdherent::latest()->first();
        $subscription->forceFill(['statut' => 'actif'])->saveQuietly();

        $this->assertTrue($client->isAdherentAt($complexe->id));
    }

    public function test_expired_unpaid_subscription_is_archived_by_cleanup_command()
    {
        [$admin, $complexe, $type] = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Cleanup',
            'email' => 'client-cleanup@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $expired = AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->subMonths(2)->toDateString(),
            'date_fin' => now()->subDay()->toDateString(),
            'montant_vente' => 100.0,
            'remise' => 0,
            'montant_apres_remise' => 100.0,
            'statut' => 'actif',
            'paye' => false,
            'reste_a_payer' => 100.0,
        ]);

        $valid = AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant_vente' => 100.0,
            'remise' => 0,
            'montant_apres_remise' => 100.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        $this->artisan('abonnements:cleanup-invalid')->assertSuccessful();

        $this->assertSoftDeleted('abonnements_adherent', ['id' => $expired->id]);
        $this->assertDatabaseHas('abonnements_adherent', ['id' => $valid->id, 'deleted_at' => null]);
    }

    public function test_unpaid_subscription_does_not_grant_adherent_benefits()
    {
        [$admin, $complexe, $type] = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Unpaid',
            'email' => 'client-unpaid@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant_vente' => 100.0,
            'remise' => 0,
            'montant_apres_remise' => 100.0,
            'statut' => 'actif',
            'paye' => false,
            'reste_a_payer' => 100.0,
        ]);

        $this->assertFalse($client->isAdherentAt($complexe->id));
    }

    public function test_subscription_discount_is_seeded_from_plan_and_refund_requires_admin_confirmation()
    {
        list($admin, $complexe, $type) = $this->setupData();
        $type->update(['discount_percentage' => 10]);

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Refund',
            'email' => 'client-refund@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $token = JWTAuth::fromUser($client);

        $create = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements/souscrire', [
                'type_abonnement_id' => $type->id,
                'modalite_paiement' => 'especes',
                'date_debut' => now()->toDateString(),
            ]);

        $create->assertStatus(201)
            ->assertJson(['success' => true]);

        $subscriptionId = $create->json('data.id');
        $this->assertNotNull($subscriptionId);

        $this->assertDatabaseHas('abonnements_adherent', [
            'id' => $subscriptionId,
            'remise' => 12.0,
            'montant_apres_remise' => 108.0,
        ]);

        AbonnementAdherent::find($subscriptionId)->update(['paye' => true]);

        $cancel = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/abonnement-adherents/{$subscriptionId}/cancel", []);

        $cancel->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('abonnements_adherent', [
            'id' => $subscriptionId,
            'refund_status' => 'pending',
        ]);

        // Refresh admin to ensure latest data and explicitly act as that admin
        $freshAdmin = User::find($admin->id);
        $confirmRefund = $this->actingAs($freshAdmin, 'api')
            ->putJson("/api/admin/abonnements-adherent/{$subscriptionId}/confirm-refund", []);

        $this->assertEquals(200, $confirmRefund->status(), "Response: " . json_encode($confirmRefund->json()));


        $this->assertDatabaseHas('abonnements_adherent', [
            'id' => $subscriptionId,
            'refund_status' => 'succeeded',
            'statut' => 'annule',
        ]);
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

    public function test_client_cannot_create_overlapping_active_subscription_for_same_type()
    {
        list($admin, $complexe, $type) = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Overlap',
            'email' => 'client-overlap@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $existing = AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->subDays(10)->toDateString(),
            'date_fin' => now()->addDays(20)->toDateString(),
            'montant_vente' => 120.0,
            'remise' => 0,
            'montant_apres_remise' => 120.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        $token = JWTAuth::fromUser($client);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/abonnements/souscrire', [
                'type_abonnement_id' => $type->id,
                'modalite_paiement' => 'especes',
                'date_debut' => now()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', 'Vous avez déjà un abonnement actif pour cette formule.');
    }

    public function test_mes_abonnements_hides_duplicate_active_subscriptions()
    {
        list($admin, $complexe, $type) = $this->setupData();

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Duplicate',
            'email' => 'client-duplicate@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->subDays(30)->toDateString(),
            'date_fin' => now()->addDays(10)->toDateString(),
            'montant_vente' => 120.0,
            'remise' => 0,
            'montant_apres_remise' => 120.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(30)->toDateString(),
            'montant_vente' => 120.0,
            'remise' => 0,
            'montant_apres_remise' => 120.0,
            'statut' => 'actif',
            'paye' => true,
            'reste_a_payer' => 0.0,
        ]);

        $token = JWTAuth::fromUser($client);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/mes-abonnements');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($type->id, $data[0]['type_abonnement_id']);
        $this->assertSame('actif', $data[0]['statut']);
    }
}
