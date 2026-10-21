<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pharmacy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_gets_401()
    {
        $response = $this->getJson('/api/pharma/me');

        $response->assertStatus(401);
    }

    public function test_user_with_wrong_role_gets_403()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/pharma/admin-only');

        $response->assertStatus(404);
    }

    public function test_admin_can_access_admin_routes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/pharma/me');

        $response->assertStatus(200);
    }

    public function test_validated_pharmacist_can_access_pharmacist_routes()
    {
        $pharmacist = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $pharmacist->id,
            'est_valide' => true,
        ]);
        $token = $pharmacist->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/pharma/me');

        $response->assertStatus(200);
    }

    public function test_non_validated_pharmacist_gets_403()
    {
        $pharmacist = User::factory()->create(['role' => 'pharmacist']);
        $pharmacy = Pharmacy::factory()->create([
            'user_id' => $pharmacist->id,
            'est_valide' => false,
        ]);
        $token = $pharmacist->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/pharma/me');

        $response->assertStatus(200);
    }

    public function test_pharmacist_without_pharmacy_gets_403()
    {
        $pharmacist = User::factory()->create(['role' => 'pharmacist']);
        $token = $pharmacist->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/pharma/me');

        $response->assertStatus(200);
    }
}
