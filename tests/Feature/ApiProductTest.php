<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiProductTest extends TestCase
{
    public function test_public_products_index_returns_only_active_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Active Product',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
            'name' => 'Inactive Product',
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Active Product'])
            ->assertJsonMissing(['name' => 'Inactive Product']);
    }

    public function test_admin_products_index_returns_all_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        /** @var Category $category */
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Active Product',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
            'name' => 'Inactive Product',
        ]);

        $response = $this->getJson('/api/admin/products');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Active Product'])
            ->assertJsonFragment(['name' => 'Inactive Product']);
    }

    public function test_show_product_returns_product_with_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
        Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);

        $response = $this->getJson("/api/products?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Product 1'])
            ->assertJsonMissing(['name' => 'Product 2']);
    }

    public function test_products_can_be_searched_by_name(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'name' => 'iPhone 15']);
        Product::factory()->create(['category_id' => $category->id, 'name' => 'Samsung Galaxy']);

        $response = $this->getJson('/api/products?search=iPhone');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'iPhone 15'])
            ->assertJsonMissing(['name' => 'Samsung Galaxy']);
    }

    public function test_products_can_be_filtered_by_price_range(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'price' => 100]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 500]);
        Product::factory()->create(['category_id' => $category->id, 'price' => 1000]);

        $response = $this->getJson('/api/products?min_price=200&max_price=800');

        $response->assertStatus(200)
            ->assertJsonFragment(['price' => '500.00'])
            ->assertJsonMissing(['price' => '100.00'])
            ->assertJsonMissing(['price' => '1000.00']);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'Product description',
            'price' => 99.99,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New Product',
                'slug' => 'new-product',
                'price' => '99.99',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'slug' => 'new-product',
        ]);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'price' => 199.99,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Product',
                'price' => '199.99',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/admin/products/{$product->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_non_admin_cannot_create_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);
        $category = Category::factory()->create();

        $response = $this->postJson('/api/admin/products', [
            'category_id' => $category->id,
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 99.99,
            'stock' => 10,
        ]);

        $response->assertStatus(403);
    }
}
