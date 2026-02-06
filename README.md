  <!-- /**
     * Get vendor request statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => VendorRequest::count(),
            'pending' => VendorRequest::pending()->count(),
            'approved' => VendorRequest::approved()->count(),
            'rejected' => VendorRequest::rejected()->count(),
            'today' => VendorRequest::whereDate('created_at', today())->count(),
            'this_week' => VendorRequest::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => VendorRequest::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    } -->
 <!-- // Admin
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->vendorRequestService->getStatistics();

            return $this->successResponse(
                $stats,
                'Statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    } -->
