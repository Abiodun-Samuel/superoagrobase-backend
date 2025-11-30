<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

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
            $products = $this->productService->getProducts($validated, $validated['per_page'] ?? null);
            return $this->paginatedResponse(ProductResource::collection($products), '');
        } catch (\Exception $ex) {
            return $this->errorResponse($ex->getMessage(), '');
        }
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if ($request->query('increment_view') === 'true') {
            $product->increment('view_count');
        }
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
}
