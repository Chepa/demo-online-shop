<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $isAdmin = ($user && $user->isAdmin());

            return response()->json($this->productService->getProducts($request, $isAdmin));
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'ProductController');
        }
    }

    public function show(Product $product): JsonResponse
    {
        try {
            $product->load('category');

            return response()->json($product);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'ProductController');
        }
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return response()->json($product, 201);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'ProductController');
        }
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $product = $this->productService->updateProduct($product, $request->validated());
            $product->load('category');

            return response()->json($product);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'ProductController');
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->deleteProduct($product);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'ProductController');
        }
    }
}


