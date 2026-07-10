<?php

namespace Tests\Feature;

use App\Models\Complexe;
use App\Models\TypeAbonnementAdherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubscriptionTypeSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_deactivate_subscription_type_instead_of_hard_deleting_it(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin-soft-delete@example.test',
            'password' => bcrypt('secret'),
            'role' => 'super_admin',
        ]);

        $complexe = Complexe::create([
            'owner_id' => $admin->id,
            'name' => 'Complexe Soft Delete',
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

        $token = JWTAuth::fromUser($admin);
        $this->actingAs($admin, 'api');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/admin/abonnements/types/'.$type->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $type->refresh();
        $this->assertFalse($type->active);
        $this->assertDatabaseHas('type_abonnement_adherent', [
            'id' => $type->id,
            'active' => false,
        ]);
    }
}
