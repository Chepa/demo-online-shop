<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCategoryTest extends TestCase
{
    public function test_public_can_view_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Electronics',
                'slug' => 'electronics',
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);
    }

    public function test_admin_can_create_category_with_parent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $parent = Category::factory()->create();

        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Smartphones',
                'parent_id' => $parent->id,
            ]);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $category = Category::factory()->create();

        $response = $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Category',
            'slug' => 'updated-category',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Category',
                'slug' => 'updated-category',
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response->assertStatus(403);
    }

    public function test_category_validation_requires_name_and_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_category_slug_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        Category::factory()->create(['slug' => 'electronics']);

        $response = $this->postJson('/api/admin/categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }
}
