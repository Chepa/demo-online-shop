<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = ($user && $user->isAdmin());

            return response()->json($this->productService->getProducts($request, $isAdmin));
        } catch (Throwable $exception) {
            Log::error('[ProductController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function show(Product $product)
    {
        try {
            $product = $this->productService->getProduct($product->id);

            return response()->json($product);
        } catch (Throwable $exception) {
            Log::error('[ProductController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function store(CreateProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return response()->json($product, 201);
        } catch (Throwable $exception) {
            Log::error('[ProductController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $product = $this->productService->updateProduct($product, $request->validated());

            return response()->json($product);
        } catch (Throwable $exception) {
            Log::error('[ProductController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function destroy(Product $product)
    {
        try {
            $this->productService->deleteProduct($product);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            Log::error('[ProductController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}


