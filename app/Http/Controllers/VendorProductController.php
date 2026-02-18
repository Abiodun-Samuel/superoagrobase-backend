<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorProduct\StoreVendorProductRequest;
use App\Http\Requests\VendorProduct\UpdateVendorProductRequest;
use App\Http\Resources\VendorProductResource;
use App\Services\VendorProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store(StoreVendorProductRequest $request)
    {
        try {
            $result = $this->vendorProductService->addProducts($request->validated()['products']);

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
    public function update(UpdateVendorProductRequest $request)
    {
        try {
            $updated = $this->vendorProductService->updateProducts($request->validated()['products']);

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
            'ids.*' => 'required|integer|exists:vendor_products,id',
        ]);

        try {
            $deleted = $this->vendorProductService->deleteProducts($validated['ids']);

            if ($deleted === 0) {
                return $this->errorResponse('No products found to delete', 404);
            }

            $message = $deleted === 1
                ? 'Product removed successfully'
                : "{$deleted} products removed successfully";

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
