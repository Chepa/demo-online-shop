<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ProductService
{
    const int DEFAULT_PAGINATION_LIMIT = 10;
    const int MAX_PAGINATION_LIMIT = 100;

    public function getProducts(Request $request, bool $isAdmin = false): LengthAwarePaginator
    {
        $query = Product::query()->with('category')->has('category');

        if (!$isAdmin) {
            $query->where('is_active', true);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->string('search')->toString()) {
            // Используем FULLTEXT поиск если доступен, иначе LIKE
            if (config('database.default') === 'mysql') {
                // Проверяем, что поля не пустые для FULLTEXT поиска
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$search])
                      ->orWhere('name', 'like', "%{$search}%");
                });
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        // Настраиваемая пагинация
        $perPage = $request->integer('per_page', self::DEFAULT_PAGINATION_LIMIT);
        $perPage = min(max($perPage, 1), self::MAX_PAGINATION_LIMIT); // Ограничение от 1 до 100

        return $query->latest('id')->paginate($perPage);
    }

    public function getProduct(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        $product = Product::create($data);
        $product->load('category');

        return $product;
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        $product->load('category');

        return $product->fresh(['category']);
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }
}
