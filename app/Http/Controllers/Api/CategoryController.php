<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategories();

            return response()->json($categories);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CategoryController');
        }
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());

            return response()->json($category, 201);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CategoryController');
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($category, $request->validated());

            return response()->json($category);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CategoryController');
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'CategoryController');
        }
    }
}


