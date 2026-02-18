<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    public function index()
    {
        $categories = Category::with(['subcategory', 'products'])->get();
        $data = CategoryResource::collection($categories);
        return $this->successResponse($data, '');
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return $this->successResponse(
            CategoryResource::make($category),
            'Category created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');

        return $this->successResponse(
            CategoryResource::make($category),
            'Category retrieved successfully'
        );
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categoryService->updateCategory($category, $request->validated());

        return $this->successResponse(
            CategoryResource::make($category),
            'Category updated successfully'
        );
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->deleteCategory($category);

        return $this->successResponse(
            null,
            'Category deleted successfully'
        );
    }
}
