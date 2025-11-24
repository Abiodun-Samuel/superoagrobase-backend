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

    public function show(Request $request, Product $product): JsonResponse
    {
        if ($request->query('increment_view') === 'true') {
            $product->increment('view_count');
        }
        return $this->successResponse(new ProductResource($product));
    }

    public function getFeaturedProducts(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 16);
        $products = $this->productService->getFeaturedProducts($limit);
        return $this->successResponse(ProductResource::collection($products), '');
    }

    public function getTrendingProducts(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 16);
        $products = $this->productService->getTrendingProducts($limit);
        return $this->successResponse(ProductResource::collection($products), '');
    }
}
