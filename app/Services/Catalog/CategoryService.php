<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    const string CACHE_KEY = 'categories_all';
    const int CACHE_TTL = 3600; // 1 час

    public function getAllCategories(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Category::query()
                ->withCount('products')
                ->orderBy('name')
                ->get();
        });
    }

    public function createCategory(array $data): Category
    {
        $category = Category::create($data);
        $this->clearCache();

        return $category;
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);
        $this->clearCache();

        return $category;
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
        $this->clearCache();
    }

    /**
     * Очистить кеш категорий
     */
    protected function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
