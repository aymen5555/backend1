<?php

namespace Tests\Feature;

use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class GerantCrossComplexeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerant_cannot_access_another_complexe_product_admin_route(): void
    {
        $gerantA = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'A',
            'email' => 'gerantA@test.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $gerantB = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'B',
            'email' => 'gerantB@test.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $complexeA = Complexe::create([
            'name' => 'Complexe A',
            'address' => 'Addr A',
            'owner_id' => $gerantA->id,
        ]);

        $complexeB = Complexe::create([
            'name' => 'Complexe B',
            'address' => 'Addr B',
            'owner_id' => $gerantB->id,
        ]);

        $categorie = \App\Models\CategorieProduit::create(['nom' => 'Test Category']);

        $produitB = Produit::create([
            'nom' => 'Produit B',
            'categorie_id' => $categorie->id,
            'complexe_id' => $complexeB->id,
            'prix' => 10,
            'sport_cible' => 'padel',
            'niveau_cible' => 'tous',
            'description' => 'Test',
            'actif' => true,
            'reference' => 'PRD-TEST-001',
        ]);

        Stock::create([
            'produit_id' => $produitB->id,
            'quantite_disponible' => 10,
            'quantite_minimale' => 1,
        ]);

        $tokenA = JWTAuth::fromUser($gerantA);

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->putJson("/api/admin/produits/{$produitB->id}", [
                'nom' => 'Updated Name',
            ]);

        $response->assertStatus(403);
        $this->assertEquals('You are not authorized for this complexe.', $response->json('message'));
    }
}
