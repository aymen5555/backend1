<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\Complexe;
use App\Models\LigneCommande;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryFlowTest extends TestCase
{
    use RefreshDatabase;

    private function getSuperAdminToken()
    {
        $admin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin-cat-test@example.test',
            'password' => bcrypt('secret'),
            'role' => 'super_admin',
        ]);

        $token = JWTAuth::fromUser($admin);
        JWTAuth::setToken($token);
        $this->actingAs($admin, 'api');

        return $token;
    }

    public function test_public_index_only_lists_active_categories()
    {
        CategorieProduit::create(['nom' => 'Active Cat', 'active' => true]);
        CategorieProduit::create(['nom' => 'Inactive Cat', 'active' => false]);

        $response = $this->getJson('/api/categories-produits');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nom', 'Active Cat');
    }

    public function test_admin_index_lists_all_categories()
    {
        $token = $this->getSuperAdminToken();

        CategorieProduit::create(['nom' => 'Active Cat', 'active' => true]);
        CategorieProduit::create(['nom' => 'Inactive Cat', 'active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/categories-produits');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_destroy_deactivates_category_if_has_products()
    {
        $token = $this->getSuperAdminToken();

        $complexe = Complexe::create([
            'name' => 'Test Complexe',
            'address' => '123 Test St',
            'city' => 'Test Ville',
            'phone' => '12345678',
        ]);

        $category = CategorieProduit::create(['nom' => 'Cat With Products', 'active' => true]);

        // Create a product in this category
        Produit::create([
            'nom' => 'Test Product',
            'description' => 'A test product',
            'prix' => 10.0,
            'image' => 'test.jpg',
            'actif' => true,
            'categorie_id' => $category->id,
            'complexe_id' => $complexe->id,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/categories-produits/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $category->refresh();
        $this->assertFalse($category->active);
        $this->assertDatabaseHas('categorie_produits', ['id' => $category->id]);
    }

    public function test_destroy_permanently_deletes_category_if_has_no_products()
    {
        $token = $this->getSuperAdminToken();

        $category = CategorieProduit::create(['nom' => 'Empty Cat', 'active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/categories-produits/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categorie_produits', ['id' => $category->id]);
    }

    public function test_product_destroy_fails_if_has_orders()
    {
        $token = $this->getSuperAdminToken();

        $complexe = Complexe::create([
            'name' => 'Test Complexe',
            'address' => '123 Test St',
            'city' => 'Test Ville',
            'phone' => '12345678',
        ]);

        $category = CategorieProduit::create(['nom' => 'Some Cat', 'active' => true]);

        $product = Produit::create([
            'nom' => 'Product With Orders',
            'prix' => 10.0,
            'actif' => true,
            'categorie_id' => $category->id,
            'complexe_id' => $complexe->id,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous',
        ]);

        // Create an order
        $client = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $commande = Commande::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'statut' => 'en_attente',
            'statut_paiement' => 'non_paye',
            'modalite_paiement' => 'carte',
            'montant_total' => 10.0,
        ]);

        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $product->id,
            'quantite' => 1,
            'prix_unitaire' => 10.0,
            'sous_total' => 10.0,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/produits/{$product->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Impossible de supprimer : ce produit a des ventes ou des commandes liées. Désactivez-le à la place.',
            ]);

        $this->assertDatabaseHas('produits', ['id' => $product->id]);
    }

    public function test_product_destroy_succeeds_if_no_history()
    {
        $token = $this->getSuperAdminToken();

        $complexe = Complexe::create([
            'name' => 'Test Complexe',
            'address' => '123 Test St',
            'city' => 'Test Ville',
            'phone' => '12345678',
        ]);

        $category = CategorieProduit::create(['nom' => 'Some Cat', 'active' => true]);

        $product = Produit::create([
            'nom' => 'Product Without History',
            'prix' => 10.0,
            'actif' => true,
            'categorie_id' => $category->id,
            'complexe_id' => $complexe->id,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/produits/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produit supprimé définitivement.',
            ]);

        $this->assertDatabaseMissing('produits', ['id' => $product->id]);
    }
}
