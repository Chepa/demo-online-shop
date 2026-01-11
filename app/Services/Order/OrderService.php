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
    public function getUserOrders(User $user, int $perPage = 10): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100); // Ограничение от 1 до 100

        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product.category'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getOrder(Order $order): Order
    {
        $order->load(['items.product.category', 'user']);

        return $order;
    }

    /**
     * @throws Throwable
     */
    public function createOrderFromCart(User $user, array $customerData): Order
    {
        return DB::transaction(function () use ($user, $customerData) {
            $cartItems = CartItem::query()
                ->with(['product' => function ($query) {
                    $query->lockForUpdate(); // Блокируем продукты для обновления stock
                }])
                ->where('user_id', $user->id)
                ->lockForUpdate() // Блокировка корзины для предотвращения race condition
                ->get();

            if ($cartItems->isEmpty()) {
                throw new Exception('Корзина пуста.');
            }

            // Проверка наличия всех товаров на складе перед созданием заказа
            foreach ($cartItems as $item) {
                /** @var CartItem $item */
                // Перезагружаем продукт с блокировкой
                $product = \App\Models\Product::lockForUpdate()->findOrFail($item->product_id);
                
                if ($product->stock < $item->quantity) {
                    throw new Exception(
                        "Недостаточно товара '{$product->name}' на складе. " .
                        "Запрошено: {$item->quantity}, доступно: {$product->stock}"
                    );
                }
                
                // Сохраняем актуальную цену продукта
                $item->product = $product;
            }

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

                // Уменьшаем количество товара на складе с проверкой
                $item->product->decrement('stock', $item->quantity);
            }

            CartItem::query()->where('user_id', $user->id)->delete();

            $order->load(['items.product.category']);
            SendOrderConfirmationEmail::dispatch($order);

            return $order;
        });
    }

    public function getAllOrders(int $perPage = 20): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100); // Ограничение от 1 до 100

        return Order::query()
            ->with(['user', 'items.product.category'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);
        $order->load(['user', 'items.product.category']);

        return $order->fresh(['user', 'items.product.category']);
    }
}
