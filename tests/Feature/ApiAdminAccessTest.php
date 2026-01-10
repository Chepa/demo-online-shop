<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAdminAccessTest extends TestCase
{
    public function test_non_admin_cannot_access_admin_products(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_categories(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_admin_orders(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(401);
    }
}
