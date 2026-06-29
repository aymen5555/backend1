<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\FournisseurInterne;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BonsStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_bon_entree_increments_stock()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'S', 'email' => 'admins@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'StockC', 'address' => 'Addr']);
        $cat = CategorieProduit::create(['nom' => 'Balls']);
        $fourn = FournisseurInterne::create(['nom_f_int' => 'Fourn', 'contact_f_int' => 'X']);
        $produit = Produit::create([
            'categorie_id' => $cat->id,
            'complexe_id' => $complexe->id,
            'nom' => 'Ball',
            'prix_achat' => 5,
            'prix' => 10,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous'
        ]);

        $token = JWTAuth::fromUser($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/bons-entree', [
                'fournisseur_interne_id' => 1,
                'complexe_id' => $complexe->id,
                'date_bon_ent' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 10, 'prix_unitaire' => 5],
                ],
            ]);

        $res->assertStatus(201);

        $this->assertDatabaseHas('stocks', ['produit_id' => $produit->id, 'quantite_disponible' => 10]);
    }

    public function test_bon_sortie_decrements_stock_and_rejects_insufficient()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'T', 'email' => 'admint@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'StockC2', 'address' => 'Addr']);
        $cat2 = CategorieProduit::create(['nom' => 'Nets']);
        $produit = Produit::create([
            'categorie_id' => $cat2->id,
            'complexe_id' => $complexe->id,
            'nom' => 'Net',
            'prix_achat' => 8,
            'prix' => 16,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous'
        ]);

        // Seed stock
        Stock::create(['produit_id' => $produit->id, 'quantite_disponible' => 5, 'quantite_minimale' => 1]);

        $token = JWTAuth::fromUser($admin);

        // Attempt to remove 3 units (should succeed)
        $ok = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/bons-sortie', [
                'complexe_id' => $complexe->id,
                'date_bon_sor' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 3],
                ],
            ]);
        $ok->assertStatus(201);
        $this->assertDatabaseHas('stocks', ['produit_id' => $produit->id, 'quantite_disponible' => 2]);

        // Attempt to remove 5 units (should fail due to insufficient stock)
        $fail = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/bons-sortie', [
                'complexe_id' => $complexe->id,
                'date_bon_sor' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 5],
                ],
            ]);
        $fail->assertStatus(422);
    }
}
