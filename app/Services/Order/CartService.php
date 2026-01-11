<?php

namespace App\Services\Order;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function getCartItems(User $user): Collection
    {
        return CartItem::query()
            ->with(['product.category'])
            ->where('user_id', $user->id)
            ->get();
    }

    public function addProductToCart(User $user, int $productId, int $quantity): CartItem
    {
        /** @var Product $product */
        $product = Product::findOrFail($productId);

        // Проверка наличия товара на складе
        if ($product->stock < $quantity) {
            throw new \Exception("Недостаточно товара на складе. Доступно: {$product->stock}");
        }

        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $quantity;
        
        // Проверка доступного количества при добавлении
        if ($product->stock < $newQuantity) {
            throw new \Exception("Недостаточно товара на складе. Доступно: {$product->stock}");
        }

        $item->quantity = $newQuantity;
        $item->save();

        return $item->load(['product.category']);
    }

    public function updateCartItemQuantity(User $user, Product $product, int $quantity): CartItem
    {
        // Проверка наличия товара на складе
        if ($product->stock < $quantity) {
            throw new \Exception("Недостаточно товара на складе. Доступно: {$product->stock}");
        }

        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $item->quantity = $quantity;
        $item->save();

        return $item->load(['product.category']);
    }

    public function removeCartItem(User $user, Product $product): void
    {
        CartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
