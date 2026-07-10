<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Complexe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Comprehensive Authorization Baseline Test
 *
 * SECURITY BASELINE: Verifies that CLIENT tokens CANNOT get 200 (success)
 * on any /admin/* route. Status 403/404/405 are acceptable - they mean blocked.
 *
 * The key assertion: if a CLIENT ever gets 200 on an admin route, that's a SECURITY HOLE.
 */
class ComprehensiveAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;
    protected User $gerantUser;
    protected User $superAdminUser;
    protected Complexe $complexe;
    protected string $clientToken;
    protected string $gerantToken;
    protected string $superAdminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->clientUser = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@test.test',
            'password' => bcrypt('password'),
            'role' => 'client',
        ]);

        $this->gerantUser = User::create([
            'first_name' => 'Gerant',
            'last_name' => 'User',
            'email' => 'gerant@test.test',
            'password' => bcrypt('password'),
            'role' => 'gerant',
        ]);

        $this->superAdminUser = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@test.test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        // Create test complexe
        $this->complexe = Complexe::create([
            'name' => 'Test Complex',
            'owner_id' => $this->gerantUser->id,
            'city' => 'Test City',
            'address' => 'Test Address',
        ]);

        // Generate tokens
        $this->clientToken = JWTAuth::fromUser($this->clientUser);
        $this->gerantToken = JWTAuth::fromUser($this->gerantUser);
        $this->superAdminToken = JWTAuth::fromUser($this->superAdminUser);
    }

    /**
     * CRITICAL TEST: CLIENT should NEVER get 200 on any /admin/* route
     * This is the security baseline ensuring role separation is working
     */
    public function test_client_cannot_get_200_on_any_admin_route(): void
    {
        $adminRoutes = [
            // Products & Commerce
            '/api/admin/produits',
            '/api/admin/categories-produits',
            '/api/admin/commandes',
            '/api/admin/ventes-directes',

            // Suppliers & Bons
            '/api/admin/fournisseurs',
            '/api/admin/fournisseurs-internes',
            '/api/admin/categories-fournisseurs',
            '/api/admin/bons-entree',
            '/api/admin/bons-sortie',

            // Expenses & Finance
            '/api/admin/types-depenses',
            '/api/admin/depenses',

            // Subscriptions
            '/api/admin/abonnements/types',
            '/api/admin/abonnements-adherent',

            // Reservations & Activities
            '/api/admin/reservations',
            '/api/admin/archives',
            '/api/admin/activites',
            '/api/admin/activites/reservations',

            // System
            '/api/super-admin/stats',
            '/api/admin/gerants',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->withHeader('Authorization', "Bearer {$this->clientToken}")
                ->getJson($route);

            $this->assertNotEquals(
                200,
                $response->status(),
                "SECURITY HOLE: CLIENT got 200 on {$route}. This should be 403/404/405."
            );
        }
    }

    /**
     * Positive test: GERANT can access their own complex admin routes
     */
    public function test_gerant_can_access_admin_routes_for_own_complex(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->gerantToken}")
            ->getJson('/api/admin/produits');

        $this->assertEquals(
            200,
            $response->status(),
            "GERANT should get 200 on /api/admin/produits for their own complex"
        );
    }

    /**
     * Positive test: SUPER_ADMIN can access all admin routes
     */
    public function test_super_admin_can_access_all_admin_routes(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->superAdminToken}")
            ->getJson('/api/admin/produits');

        $this->assertEquals(
            200,
            $response->status(),
            "SUPER_ADMIN should get 200 on /api/admin/produits"
        );
    }

    /**
     * Unauthenticated requests should be blocked
     */
    public function test_unauthenticated_requests_blocked(): void
    {
        $response = $this->getJson('/api/admin/produits');

        $this->assertEquals(
            401,
            $response->status(),
            "Unauthenticated request to /api/admin/produits should return 401"
        );
    }
}
