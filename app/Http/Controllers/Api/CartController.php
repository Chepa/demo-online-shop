<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Order\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $items = $this->cartService->getCartItems($user);

            return response()->json($items);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CartController');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => ['required', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1', 'max:1000'], // Максимальное количество
            ]);

            $user = $request->user();
            $item = $this->cartService->addProductToCart($user, $validated['product_id'], $validated['quantity']);

            return response()->json($item, 201);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CartController');
        }
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:1', 'max:1000'], // Максимальное количество
            ]);

            $user = $request->user();
            $item = $this->cartService->updateCartItemQuantity($user, $product, $validated['quantity']);

            return response()->json($item);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CartController');
        }
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        try {
            $user = $request->user();
            $this->cartService->removeCartItem($user, $product);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CartController');
        }
    }
}


