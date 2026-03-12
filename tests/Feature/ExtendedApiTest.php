<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtendedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_protected_route_denies_guest()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_sanctum_protected_route_allows_authenticated_user()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJson(['email' => $user->email]);
    }

    public function test_multi_tenant_middleware_requires_header()
    {
        $response = $this->getJson('/api/tenant/test');
        $response->assertStatus(400)
                 ->assertJson(['error' => 'Tenant ID is required']);
    }

    public function test_multi_tenant_middleware_accepts_header()
    {
        $response = $this->getWithHeaders([
            'X-Tenant-ID' => 'tenant_1'
        ], '/api/tenant/test');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Multi-tenant middleware working!',
                     'tenant_id' => 'tenant_1'
                 ]);
    }

    private function getWithHeaders(array $headers, $uri)
    {
        return $this->getJson($uri, $headers);
    }
}
