<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\FournisseurInterne;
use App\Models\LigneBonEntree;
use App\Models\LigneBonSortie;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BonReglementTest extends TestCase
{
    use RefreshDatabase;

    public function test_bon_entree_partial_and_full_payment()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'BE', 'email' => 'adminbe@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'CBE', 'address' => 'Addr']);
        $cat = CategorieProduit::create(['nom' => 'Supplies']);
        $fourn = FournisseurInterne::create(['nom_f_int' => 'LocalF', 'contact_f_int' => 'X']);

        $produit = Produit::create([
            'categorie_id' => $cat->id,
            'complexe_id' => $complexe->id,
            'nom' => 'ItemBE',
            'prix_achat' => 10,
            'prix' => 20,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous'
        ]);

        $token = JWTAuth::fromUser($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/bons-entree', [
                'fournisseur_interne_id' => $fourn->id,
                'complexe_id' => $complexe->id,
                'date_bon_ent' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 5, 'prix_unitaire' => 10],
                ],
            ]);

        $res->assertStatus(201);
        $bonId = $res->json('data.id');
        $total = $res->json('data.total_ttc_bon_ent');

        // Partial payment
        $partial = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/bons-entree/{$bonId}/confirmer-paiement", ['montant' => $total / 2, 'reference' => 'REF1']);

        $partial->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('reglement_bon_entrees', ['bon_entree_id' => $bonId, 'montant' => number_format($total / 2, 2, '.', '')]);
        $this->assertDatabaseHas('bon_entrees', ['id' => $bonId, 'statut_paiement' => 'partiel']);

        // Pay remaining
        $remaining = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/bons-entree/{$bonId}/confirmer-paiement", ['montant' => $total / 2, 'reference' => 'REF2']);

        $remaining->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('bon_entrees', ['id' => $bonId, 'statut_paiement' => 'paye']);
    }

    public function test_bon_sortie_partial_and_full_payment()
    {
        $admin = User::create(['first_name' => 'Admin', 'last_name' => 'BS', 'email' => 'adminbs@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => $admin->id, 'name' => 'CBS', 'address' => 'Addr']);
        $cat = CategorieProduit::create(['nom' => 'Goods']);

        $produit = Produit::create([
            'categorie_id' => $cat->id,
            'complexe_id' => $complexe->id,
            'nom' => 'ItemBS',
            'prix_achat' => 15,
            'prix' => 30,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous'
        ]);

        // seed stock so bon sortie can be created
        Stock::create(['produit_id' => $produit->id, 'quantite_disponible' => 10, 'quantite_minimale' => 1]);

        $token = JWTAuth::fromUser($admin);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/bons-sortie', [
                'complexe_id' => $complexe->id,
                'date_bon_sor' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 2],
                ],
            ]);

        $res->assertStatus(201);
        $bonId = $res->json('data.id');
        $total = $res->json('data.total_ttc_bon_sor');

        // Partial payment
        $partial = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/bons-sortie/{$bonId}/confirmer-paiement", ['montant' => $total / 2, 'reference' => 'R1']);

        $partial->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('reglement_bon_sorties', ['bon_sortie_id' => $bonId, 'montant' => number_format($total / 2, 2, '.', '')]);
        $this->assertDatabaseHas('bon_sorties', ['id' => $bonId, 'statut_paiement' => 'partiel']);

        // Pay remaining
        $remaining = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/bons-sortie/{$bonId}/confirmer-paiement", ['montant' => $total / 2, 'reference' => 'R2']);

        $remaining->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('bon_sorties', ['id' => $bonId, 'statut_paiement' => 'paye']);
    }
}
