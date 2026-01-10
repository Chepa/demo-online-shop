<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_user_can_create_order_from_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100,
            'stock' => 5,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $customerData = [
            'customer_name' => 'Test Customer',
            'customer_phone' => '1234567890',
            'address_line' => '123 Test St',
            'city' => 'Test City',
            'postal_code' => '12345',
        ];

        $response = $this->postJson('/api/orders', $customerData);

        $response->assertStatus(201)
            ->assertJson([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'total' => '200.00', // 2 * 100
                'customer_name' => 'Test Customer',
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'total' => 200.00,
            'customer_name' => 'Test Customer',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $response->json('id'),
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Корзина должна быть очищена
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_view_their_orders(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order->id]);
    }

    public function test_admin_can_view_all_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order1 = Order::factory()->create(['user_id' => $user1->id]);
        $order2 = Order::factory()->create(['user_id' => $user2->id]);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $order1->id])
            ->assertJsonFragment(['id' => $order2->id]);
    }

    public function test_admin_can_update_order_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $response = $this->patchJson("/api/admin/orders/{$order->id}", [
            'status' => Order::STATUS_PAID,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $order->id,
                'status' => Order::STATUS_PAID,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PAID,
        ]);
    }

    public function test_non_admin_cannot_update_order_status(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);
        $order = Order::factory()->create();

        $response = $this->patchJson("/api/admin/orders/{$order->id}", [
            'status' => Order::STATUS_PAID,
        ]);

        $response->assertStatus(403);
    }
}
