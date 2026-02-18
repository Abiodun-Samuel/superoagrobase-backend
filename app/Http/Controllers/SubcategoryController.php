<?php

namespace App\Http\Controllers;

use App\Services\SubcategoryService;
use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Requests\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubcategoryController extends Controller
{
    protected SubcategoryService $subcategoryService;

    public function __construct(SubcategoryService $subcategoryService)
    {
        $this->subcategoryService = $subcategoryService;
    }

    public function index(): JsonResponse
    {
        $subcategories = $this->subcategoryService->getAllSubcategories();

        return $this->successResponse(
            SubcategoryResource::collection($subcategories),
            'Subcategories retrieved successfully'
        );
    }

    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $subcategory = $this->subcategoryService->createSubcategory($request->validated());

        return $this->successResponse(
            SubcategoryResource::make($subcategory),
            'Subcategory created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(Subcategory $subcategory): JsonResponse
    {
        $subcategory->loadCount('products');

        return $this->successResponse(
            SubcategoryResource::make($subcategory),
            'Subcategory retrieved successfully'
        );
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        $subcategory = $this->subcategoryService->updateSubcategory($subcategory, $request->validated());

        return $this->successResponse(
            SubcategoryResource::make($subcategory),
            'Subcategory updated successfully'
        );
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $this->subcategoryService->deleteSubcategory($subcategory);

        return $this->successResponse(
            null,
            'Subcategory deleted successfully'
        );
    }
}
