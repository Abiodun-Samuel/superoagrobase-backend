<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkFeatureProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'minPrice' => 'nullable|numeric|min:0',
            'maxPrice' => 'nullable|numeric|min:0',
            'inStock' => 'nullable',
            'sort' => 'nullable|string|in:newest,oldest,price_asc,price_desc,name_asc,name_desc,popular',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $products = $this->productService->getProducts($validated, $validated['per_page'] ?? 50);
            return $this->paginatedResponse(ProductResource::collection($products), '');
        } catch (\Exception $ex) {
            return $this->errorResponse($ex->getMessage(), 500);
        }
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if ($request->query('increment_view') === 'true') {
            $product->increment('view_count');
        }
        $product->load(['category', 'subcategory']);
        return $this->successResponse(new ProductResource($product));
    }

    public function getFeaturedProducts(Request $request): JsonResponse
    {
        $per_page = $request->integer('per_page', 16);
        $products = $this->productService->getFeaturedProducts($per_page);
        return $this->successResponse(ProductResource::collection($products), '');
    }

    public function getTrendingProducts(Request $request): JsonResponse
    {
        $per_page = $request->integer('per_page', 16);
        $products = $this->productService->getTrendingProducts($per_page);
        return $this->successResponse(ProductResource::collection($products), '');
    }

    public function bulkUpdateFeatured(BulkFeatureProductRequest $request): JsonResponse
    {
        try {
            $result = $this->productService->bulkUpdateFeatured(
                $request->input('product_ids'),
                $request->boolean('is_featured')
            );

            return $this->successResponse($result, 'Update successful', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update featured status: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id',
            'subcategory_id',
            'status',
            'is_featured',
            'search',
            'sort_by',
            'sort_direction',
            'per_page'
        ]);

        $products = $this->productService->getAllProducts($filters);

        return $this->paginatedResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    public function adminShow(Request $request, Product $product): JsonResponse
    {
        $product->load([
            'category',
            'subcategory',
            'vendorProducts' => function ($query) {
                $query->with('vendor')->without('product');
            }
        ]);
        return $this->successResponse(new ProductResource($product));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return $this->successResponse(
            ProductResource::make($product),
            'Product created successfully',
            Response::HTTP_CREATED
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->updateProduct($product, $request->validated());

        return $this->successResponse(
            'ProductResource::make($product)',
            'Product updated successfully'
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }
}
