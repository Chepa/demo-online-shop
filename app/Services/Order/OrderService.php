<?php

namespace App\Services\Order;

use App\Jobs\SendOrderConfirmationEmail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderService
{
    public function getUserOrders(User $user): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->paginate(10);
    }

    public function getOrder(Order $order): Order
    {
        $order->load('items.product');

        return $order;
    }

    /**
     * @throws Throwable
     */
    public function createOrderFromCart(User $user, array $customerData): Order
    {
        $cartItems = CartItem::query()
            ->with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            throw new Exception('Корзина пуста.');
        }

        return DB::transaction(function () use ($user, $customerData, $cartItems) {
            $total = $cartItems->sum(function (CartItem $item) {
                return $item->product->price * $item->quantity;
            });

            /** @var Order $order */
            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'total' => $total,
                ...$customerData,
            ]);

            foreach ($cartItems as $item) {
                /** @var CartItem $item */
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);
            }

            CartItem::query()->where('user_id', $user->id)->delete();

            $order->load('items.product');
            SendOrderConfirmationEmail::dispatch($order);

            return $order;
        });
    }

    public function getAllOrders(): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'items.product'])
            ->latest()
            ->paginate(20);
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);
        $order->load(['user', 'items.product']);

        return $order;
    }
}
