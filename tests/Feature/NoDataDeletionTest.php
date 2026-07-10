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

class NoDataDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_delete_subscription_preserves_the_record(): void
    {
        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'Keep',
            'email' => 'keep-record@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => 'Adresse',
            'city' => 'Tunis',
            'is_active' => true,
        ]);

        $type = TypeAbonnementAdherent::create([
            'complexe_id' => $complexe->id,
            'nom' => 'Basic',
            'description' => 'desc',
            'nb_mois' => 1,
            'tarif' => 50,
            'prix_unitaire' => 50,
            'niveau_sportif_cible' => 'tous',
            'active' => true,
        ]);

        $sub = AbonnementAdherent::create([
            'user_id' => $user->id,
            'complexe_id' => $complexe->id,
            'type_abonnement_id' => $type->id,
            'date_debut' => Carbon::today()->toDateString(),
            'date_fin' => Carbon::today()->addMonth()->toDateString(),
            'montant_vente' => 50,
            'remise' => 0,
            'montant_apres_remise' => 50,
            'statut' => 'annule',
            'paye' => false,
            'reste_a_payer' => 50,
        ]);

        $token = JWTAuth::fromUser($user);
        JWTAuth::setToken($token);
        $this->actingAs($user, 'api');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/abonnement-adherents/' . $sub->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('abonnements_adherent', ['id' => $sub->id]);
    }
}
