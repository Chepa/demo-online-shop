<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ProductService
{
    const int PAGINATION_LIMIT = 10;

    public function getProducts(Request $request, bool $isAdmin = false): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if (!$isAdmin) {
            $query->where('is_active', true);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        return $query->latest('id')->paginate(self::PAGINATION_LIMIT);
    }

    public function getProduct(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }
}
