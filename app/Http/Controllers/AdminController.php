<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    protected AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = $this->adminService->getDashboardStats();

            return $this->successResponse(
                $stats,
                'Dashboard statistics retrieved successfully',
                Response::HTTP_OK
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    public function clearCache(): JsonResponse
    {
        try {
            $this->adminService->clearDashboardCache();

            return $this->successResponse(
                null,
                'Dashboard cache cleared successfully',
                Response::HTTP_OK
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
