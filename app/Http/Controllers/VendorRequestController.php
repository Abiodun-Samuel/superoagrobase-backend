<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest\ReviewVendorRequestRequest;
use App\Http\Requests\VendorRequest\SubmitVendorRequestRequest;
use App\Http\Resources\VendorRequestResource;
use App\Models\VendorRequest;
use App\Services\VendorRequestService;
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\Response;

class VendorRequestController extends Controller
{
    public function __construct(
        protected VendorRequestService $vendorRequestService
    ) {}

    public function store(SubmitVendorRequestRequest $request): JsonResponse
    {
        try {
            $vendorRequest = $this->vendorRequestService->submitRequest(
                $request->validated()
            );

            return $this->successResponse(
                new VendorRequestResource($vendorRequest),
                'Vendor request submitted successfully! We will review your application and get back to you soon.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getByEmail(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|email:rfc,dns|string'
            ]);

            $vendorRequest = $this->vendorRequestService->getRequestByEmail($request->email);
            if (!$vendorRequest) {
                return $this->successResponse(
                    null,
                    'No vendor request found for this email'
                );
            }

            return $this->successResponse(
                new VendorRequestResource($vendorRequest),
                'Vendor request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $vendorRequests = $this->vendorRequestService->getAllRequests([
                'status' => $request->query('status'),
                'search' => $request->query('search'),
            ]);

            return $this->successResponse(
                VendorRequestResource::collection($vendorRequests),
                'Vendor requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(VendorRequest $vendorRequest): JsonResponse
    {
        try {
            $vendorRequest->loadMissing(['reviewer', 'user']);

            return $this->successResponse(
                new VendorRequestResource($vendorRequest),
                'Vendor request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function approve(VendorRequest $vendorRequest): JsonResponse
    {
        try {
            $user = $this->vendorRequestService->approveRequest(
                $vendorRequest,
                auth()->user()
            );

            return $this->successResponse(
                [
                    'vendor_request' => new VendorRequestResource($vendorRequest->fresh(['reviewer', 'user'])),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'company_name' => $user->company_name,
                        'role' => $user->roles->pluck('name')->first(),
                        'created_at' => $user->created_at->toISOString(),
                    ],
                ],
                'Vendor request approved successfully! User account created and activation email sent.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    public function reject(VendorRequest $vendorRequest, ReviewVendorRequestRequest $request): JsonResponse
    {
        try {
            $this->vendorRequestService->rejectRequest(
                $vendorRequest,
                auth()->user(),
                $request->validated('rejection_reason')
            );

            return $this->successResponse(
                new VendorRequestResource($vendorRequest->fresh('reviewer')),
                'Vendor request rejected successfully. Notification sent to applicant.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
