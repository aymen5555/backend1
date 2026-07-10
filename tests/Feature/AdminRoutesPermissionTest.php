<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminRoutesPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_access_admin_bons_entree()
    {
        $client = User::create(['first_name' => 'Cli', 'last_name' => 'One', 'email' => 'client1@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $token = JWTAuth::fromUser($client);

        $res = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/admin/bons-entree');

        $res->assertStatus(403);
    }

    public function test_client_cannot_access_admin_produits_index()
    {
        $client = User::create(['first_name' => 'Cli', 'last_name' => 'Two', 'email' => 'client2@t.test', 'password' => bcrypt('secret'), 'role' => 'client']);
        $token = JWTAuth::fromUser($client);

        $res = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/admin/produits');

        $res->assertStatus(403);
    }
}
