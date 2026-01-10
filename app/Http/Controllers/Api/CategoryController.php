<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {
    }

    public function index()
    {
        try {
            $categories = $this->categoryService->getAllCategories();

            return response()->json($categories);
        } catch (Throwable $exception) {
            Log::error('[CategoryController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function store(CreateCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());

            return response()->json($category, 201);
        } catch (Throwable $exception) {
            Log::error('[CategoryController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $category = $this->categoryService->updateCategory($category, $request->validated());

            return response()->json($category);
        } catch (Throwable $exception) {
            Log::error('[CategoryController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([], 204);
        } catch (Throwable $exception) {
            Log::error('[CategoryController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}


