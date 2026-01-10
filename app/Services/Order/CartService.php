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
            ->with('product')
            ->where('user_id', $user->id)
            ->get();
    }

    public function addProductToCart(User $user, int $productId, int $quantity): CartItem
    {
        /** @var Product $product */
        $product = Product::findOrFail($productId);

        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->save();

        return $item->load('product');
    }

    public function updateCartItemQuantity(User $user, Product $product, int $quantity): CartItem
    {
        $item = CartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $item->quantity = $quantity;
        $item->save();

        return $item->load('product');
    }

    public function removeCartItem(User $user, Product $product): void
    {
        CartItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete();
    }
}
