<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorProductResource;
use App\Services\VendorProductService;
use Illuminate\Http\Request;

class VendorProductController extends Controller
{
    protected $vendorProductService;

    public function __construct(VendorProductService $vendorProductService)
    {
        $this->vendorProductService = $vendorProductService;
    }

    /**
     * Get vendor's products
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sort', 'vendor_id']);
        $vendorId = $filters['vendor_id'];

        $products = $this->vendorProductService->getVendorProducts($filters, $vendorId);

        return $this->successResponse(
            VendorProductResource::collection($products),
            'Vendor products retrieved successfully'
        );
    }

    /**
     * Add products to catalog (handles both single and bulk)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.vendor_id' => 'required|exists:users,id',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.stock' => 'required|integer|min:0',
            'products.*.is_available' => 'required|boolean',
        ]);

        try {
            $result = $this->vendorProductService->addProducts($validated['products']);

            $message = count($result['products']) === 1
                ? 'Product added to your catalog successfully'
                : "{$result['added']} product(s) added to your catalog successfully";

            if ($result['skipped'] > 0) {
                $message .= " ({$result['skipped']} already existed)";
            }

            return $this->successResponse(
                VendorProductResource::collection($result['products']),
                $message,
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update products (handles both single and bulk)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:vendor_products,id',
            'products.*.price' => 'sometimes|numeric|min:0',
            'products.*.stock' => 'sometimes|integer|min:0',
            'products.*.is_available' => 'sometimes|boolean',
        ]);

        try {
            $updated = $this->vendorProductService->updateProducts($validated['products']);

            $message = count($updated) === 1
                ? 'Product updated successfully'
                : count($updated) . ' product(s) updated successfully';

            return $this->successResponse(
                VendorProductResource::collection($updated),
                $message
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete products (handles both single and bulk)
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:vendor_products,id',
        ]);

        try {
            $deleted = $this->vendorProductService->deleteProducts($validated['ids']);

            $message = $deleted === 1
                ? 'Product removed from your catalog successfully'
                : "{$deleted} product(s) removed from your catalog successfully";

            return $this->successResponse(null, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get single vendor product
     */
    public function show($id)
    {
        try {
            $product = $this->vendorProductService->getVendorProduct($id);

            return $this->successResponse(
                new VendorProductResource($product),
                'Product retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Product not found', 404);
        }
    }
}
