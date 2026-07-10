<?php

namespace Tests\Feature;

use App\Http\Controllers\CommandeController;
use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\Complexe;
use App\Models\LigneCommande;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CommandeRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_card_order_cancel_marks_refund_pending_for_admin_confirmation(): void
    {
        $client = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Client',
            'email' => 'alice@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
        ]);

        $categorie = CategorieProduit::create([
            'nom' => 'Catégorie Test',
            'active' => true,
        ]);

        $produit = Produit::create([
            'nom' => 'Produit Test',
            'description' => 'Produit test',
            'prix' => 25.0,
            'image' => 'test.jpg',
            'actif' => true,
            'complexe_id' => $complexe->id,
            'categorie_id' => $categorie->id,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous',
        ]);

        Stock::create([
            'produit_id' => $produit->id,
            'quantite_disponible' => 10,
        ]);

        $commande = Commande::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'statut' => 'en_attente',
            'statut_paiement' => 'paye',
            'modalite_paiement' => 'carte',
            'stripe_payment_intent_id' => 'pi_test_123',
            'refund_status' => 'not_requested',
            'montant_total' => 25.0,
        ]);

        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $produit->id,
            'quantite' => 1,
            'prix_unitaire' => 25.0,
            'sous_total' => 25.0,
        ]);

        $token = JWTAuth::fromUser($client);
        $this->actingAs($client, 'api');
        JWTAuth::setToken($token);

        $controller = new class extends CommandeController {
        };

        $response = $controller->annuler($commande);

        $this->assertSame(200, $response->getStatusCode());
        $commande->refresh();
        $this->assertSame('annulee', $commande->statut);
        $this->assertSame('pending', $commande->refund_status);
        $this->assertNull($commande->refund_reference);
    }

    public function test_paid_card_order_without_stripe_intent_is_flagged_pending_for_manual_refund(): void
    {
        $client = User::create([
            'first_name' => 'Bob',
            'last_name' => 'Client',
            'email' => 'bob@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
        ]);

        $categorie = CategorieProduit::create([
            'nom' => 'Catégorie Test',
            'active' => true,
        ]);

        $produit = Produit::create([
            'nom' => 'Produit Test',
            'description' => 'Produit test',
            'prix' => 25.0,
            'image' => 'test.jpg',
            'actif' => true,
            'complexe_id' => $complexe->id,
            'categorie_id' => $categorie->id,
            'sport_cible' => 'general',
            'niveau_cible' => 'tous',
        ]);

        Stock::create([
            'produit_id' => $produit->id,
            'quantite_disponible' => 10,
        ]);

        $commande = Commande::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'statut' => 'en_attente',
            'statut_paiement' => 'paye',
            'modalite_paiement' => 'carte',
            'stripe_payment_intent_id' => null,
            'refund_status' => 'not_requested',
            'montant_total' => 25.0,
        ]);

        LigneCommande::create([
            'commande_id' => $commande->id,
            'produit_id' => $produit->id,
            'quantite' => 1,
            'prix_unitaire' => 25.0,
            'sous_total' => 25.0,
        ]);

        $token = JWTAuth::fromUser($client);
        $this->actingAs($client, 'api');
        JWTAuth::setToken($token);

        $controller = new class extends CommandeController {
        }
        ;

        $response = $controller->annuler($commande);

        $this->assertSame(200, $response->getStatusCode());
        $commande->refresh();
        $this->assertSame('annulee', $commande->statut);
        $this->assertSame('pending', $commande->refund_status);
        $this->assertNull($commande->refund_reference);
    }

    public function test_client_cannot_confirm_refund_for_order(): void
    {
        $client = User::create([
            'first_name' => 'Carol',
            'last_name' => 'Client',
            'email' => 'carol@example.test',
            'password' => bcrypt('secret'),
            'role' => 'client',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
        ]);

        $commande = Commande::create([
            'user_id' => $client->id,
            'complexe_id' => $complexe->id,
            'statut' => 'annulee',
            'statut_paiement' => 'paye',
            'modalite_paiement' => 'carte',
            'stripe_payment_intent_id' => 'pi_test_123',
            'refund_status' => 'pending',
            'montant_total' => 25.0,
        ]);

        $this->actingAs($client, 'api');

        $response = (new CommandeController())->confirmerRemboursement($commande);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_gerant_can_confirm_refund_for_order(): void
    {
        $gerant = User::create([
            'first_name' => 'Gérard',
            'last_name' => 'Owner',
            'email' => 'gerant@example.test',
            'password' => bcrypt('secret'),
            'role' => 'gerant',
        ]);

        $complexe = Complexe::create([
            'name' => 'Complexe Test',
            'address' => '123 Rue Test',
            'city' => 'Tunis',
            'phone' => '11111111',
            'owner_id' => $gerant->id,
        ]);

        $commande = Commande::create([
            'user_id' => $gerant->id,
            'complexe_id' => $complexe->id,
            'statut' => 'annulee',
            'statut_paiement' => 'paye',
            'modalite_paiement' => 'carte',
            'stripe_payment_intent_id' => 'pi_test_123',
            'refund_status' => 'pending',
            'montant_total' => 25.0,
        ]);

        $this->actingAs($gerant, 'api');

        $controller = new class extends CommandeController
        {
            protected function createStripeRefund(string $paymentIntentId, Commande $commande): array
            {
                return [
                    'id' => 're_test_456',
                    'status' => 'succeeded',
                ];
            }
        };

        $response = $controller->confirmerRemboursement($commande);

        $this->assertSame(200, $response->getStatusCode());
        $commande->refresh();
        $this->assertSame('succeeded', $commande->refund_status);
        $this->assertSame('re_test_456', $commande->refund_reference);
        $this->assertSame('paye', $commande->statut_paiement);
    }
}
