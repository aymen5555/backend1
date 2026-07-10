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

class RefundAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_refund_endpoint()
    {
        $admin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin-test@example.test',
            'password' => bcrypt('secret'),
            'role' => 'super_admin',
        ]);

        $complexe = Complexe::create([
            'owner_id' => $admin->id,
            'name' => 'Test Complexe',
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

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Test',
            'email' => 'client-test@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $sub = AbonnementAdherent::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'montant_vente' => 100.0,
            'remise' => 0,
            'montant_apres_remise' => 100.0,
            'statut' => 'annule',
            'paye' => true,
            'reste_a_payer' => 0,
            'refund_status' => 'pending',
        ]);

        $adminToken = JWTAuth::fromUser($admin);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->putJson("/api/admin/abonnements-adherent/{$sub->id}/confirm-refund", []);

        $this->assertEquals(200, $response->status(), "Response was: " . $response->content());
    }
}
