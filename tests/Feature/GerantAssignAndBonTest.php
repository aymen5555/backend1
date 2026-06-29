<?php

namespace Tests\Feature;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\FournisseurInterne;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class GerantAssignAndBonTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_assigns_complexe_to_gerant_and_gerant_creates_bon_entree()
    {
        $super = User::create(['first_name' => 'SA', 'last_name' => 'One', 'email' => 'sa1@t.test', 'password' => bcrypt('secret'), 'role' => 'super_admin']);
        $complexe = Complexe::create(['owner_id' => null, 'name' => 'AssignC', 'address' => 'Addr']);

        $gerant = User::create(['first_name' => 'Gerant', 'last_name' => 'One', 'email' => 'ger1@t.test', 'password' => bcrypt('secret'), 'role' => 'gerant']);

        $superToken = JWTAuth::fromUser($super);

        // Assign complexe to gerant via API
        $assign = $this->withHeader('Authorization', "Bearer {$superToken}")
            ->putJson("/api/admin/gerants/{$gerant->id}/complexe", ['complexe_id' => $complexe->id]);

        $assign->assertStatus(200)->assertJsonPath('data.complexe.id', $complexe->id);

        // Prepare product and fournisseur
        $cat = CategorieProduit::create(['nom' => 'Equip']);
        $produit = Produit::create([
            'categorie_id' => $cat->id,
            'complexe_id' => $complexe->id,
            'nom' => 'Racket',
            'prix_achat' => 20,
            'prix' => 40,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous'
        ]);
        $fourn = FournisseurInterne::create(['nom_f_int' => 'LocalF']);

        $gerantToken = JWTAuth::fromUser($gerant);

        // Gerant creates bon d'entree
        $create = $this->withHeader('Authorization', "Bearer {$gerantToken}")
            ->postJson('/api/admin/bons-entree', [
                'fournisseur_interne_id' => $fourn->id,
                'complexe_id' => $complexe->id,
                'date_bon_ent' => now()->format('Y-m-d'),
                'lignes' => [
                    ['produit_id' => $produit->id, 'quantite' => 7, 'prix_unitaire' => 20],
                ],
            ]);

        $create->assertStatus(201);

        $this->assertDatabaseHas('stocks', ['produit_id' => $produit->id, 'quantite_disponible' => 7]);
    }
}
