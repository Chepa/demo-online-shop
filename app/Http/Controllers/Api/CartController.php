<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Order\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $items = $this->cartService->getCartItems($user);

            return response()->json($items);
        } catch (Throwable $exception) {
            Log::error('[CartController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => ['required', 'exists:products,id'],
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            $user = $request->user();
            $item = $this->cartService->addProductToCart($user, $validated['product_id'], $validated['quantity']);

            return response()->json($item, 201);
        } catch (Throwable $exception) {
            Log::error('[CartController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:1'],
            ]);

            $user = $request->user();
            $item = $this->cartService->updateCartItemQuantity($user, $product, $validated['quantity']);

            return response()->json($item);
        } catch (Throwable $exception) {
            Log::error('[CartController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function destroy(Request $request, Product $product)
    {
        try {
            $user = $request->user();
            $this->cartService->removeCartItem($user, $product);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            Log::error('[CartController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}


