<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class VenteDirecteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerant_can_create_vente_directe_for_own_complexe_and_logs_payment(): void
    {
        $gerant = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'One',
            'email' => 'gerant-own@test.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Owned',
            'address' => 'Address Owned',
            'owner_id' => $gerant->id,
        ]);

        $categorie = CategorieProduit::create(['nom' => 'Cat A']);

        $produit = Produit::create([
            'nom' => 'Produit A',
            'categorie_id' => $categorie->id,
            'complexe_id' => $complexe->id,
            'prix' => 15,
            'sport_cible' => 'padel',
            'niveau_cible' => 'tous',
            'description' => 'Test produit',
            'actif' => true,
            'reference' => 'PRD-A-001',
        ]);

        Stock::create([
            'produit_id' => $produit->id,
            'quantite_disponible' => 10,
            'quantite_minimale' => 1,
        ]);

        $token = JWTAuth::fromUser($gerant);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/ventes-directes', [
                'complexe_id' => $complexe->id,
                'modalite_paiement' => 'especes',
                'lignes' => [[
                    'produit_id' => $produit->id,
                    'quantite' => 2,
                ]],
            ]);

        $response->assertStatus(201)->assertJson(['success' => true, 'count' => 1]);

        $this->assertDatabaseHas('vente_directes', [
            'complexe_id' => $complexe->id,
            'produit_id' => $produit->id,
            'quantite' => 2,
            'montant_total' => 30,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $gerant->id,
            'action' => 'payment',
            'model_type' => 'VenteDirecte',
        ]);
    }

    public function test_gerant_cannot_create_vente_directe_for_another_complexe(): void
    {
        $gerantA = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'A',
            'email' => 'gerant-a@test.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $gerantB = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'B',
            'email' => 'gerant-b@test.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $complexeA = Complexe::create([
            'name' => 'Complexe A',
            'address' => 'Address A',
            'owner_id' => $gerantA->id,
        ]);

        $complexeB = Complexe::create([
            'name' => 'Complexe B',
            'address' => 'Address B',
            'owner_id' => $gerantB->id,
        ]);

        $categorie = CategorieProduit::create(['nom' => 'Cat B']);

        $produitB = Produit::create([
            'nom' => 'Produit B',
            'categorie_id' => $categorie->id,
            'complexe_id' => $complexeB->id,
            'prix' => 20,
            'sport_cible' => 'tennis',
            'niveau_cible' => 'tous',
            'description' => 'Test produit B',
            'actif' => true,
            'reference' => 'PRD-B-001',
        ]);

        Stock::create([
            'produit_id' => $produitB->id,
            'quantite_disponible' => 5,
            'quantite_minimale' => 1,
        ]);

        $tokenA = JWTAuth::fromUser($gerantA);

        $response = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson('/api/admin/ventes-directes', [
                'complexe_id' => $complexeB->id,
                'modalite_paiement' => 'especes',
                'lignes' => [[
                    'produit_id' => $produitB->id,
                    'quantite' => 1,
                ]],
            ]);

        $response->assertStatus(403);
        $this->assertEquals('You are not authorized for this complexe.', $response->json('message'));
    }
}
