<?php

namespace App\Services;

use App\Models\{User, Product, Category, Order, Review, VendorRequest, VendorProduct};
use Illuminate\Support\Facades\{DB, Cache};
use Carbon\Carbon;

class AdminService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const LOW_STOCK_THRESHOLD = 10;
    private const RECENT_ITEMS_LIMIT = 10;
    private const TOP_ITEMS_LIMIT = 10;

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'overview' => $this->getOverviewStats(),
            'revenue' => $this->getRevenueMetrics(),
            'orders' => $this->getOrderAnalytics(),
            'products' => $this->getProductAnalytics(),
            'vendors' => $this->getVendorAnalytics(),
            'customers' => $this->getCustomerAnalytics(),
            'reviews' => $this->getReviewMetrics(),
            'categories' => $this->getCategoryPerformance(),
            'trends' => $this->getTrendAnalytics(),
            'recent_activities' => $this->getRecentActivities(),
        ];
    }

    /**
     * Overview statistics with optimized counting
     */
    private function getOverviewStats(): array
    {
        return Cache::remember('admin.dashboard.overview', self::CACHE_TTL, function () {
            // User counts by role - single query
            $userStats = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereNull('users.deleted_at')
                ->select('roles.name', DB::raw('COUNT(DISTINCT users.id) as count'))
                ->groupBy('roles.name')
                ->pluck('count', 'name');

            // Order status counts - single query
            $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Payment status counts - single query
            $ordersByPaymentStatus = Order::select('payment_status', DB::raw('COUNT(*) as count'))
                ->groupBy('payment_status')
                ->pluck('count', 'payment_status')
                ->toArray();

            // Product stock statistics - single query
            $productStats = Product::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock BETWEEN 1 AND ? THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured
            ', [self::LOW_STOCK_THRESHOLD])->first();

            // Review statistics - single query
            $reviewStats = Review::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as pending,
                AVG(CASE WHEN is_published = 1 THEN rating ELSE NULL END) as avg_rating
            ')->first();

            return [
                'users' => [
                    'total_customers' => $userStats['user'] ?? 0,
                    'total_vendors' => $userStats['vendor'] ?? 0,
                    'total_admins' => $userStats['admin'] ?? 0,
                    'active_users' => User::where('status', 'active')->whereNull('deleted_at')->count(),
                ],
                'products' => [
                    'total' => (int) $productStats->total,
                    'in_stock' => (int) $productStats->in_stock,
                    'out_of_stock' => (int) $productStats->out_of_stock,
                    'low_stock' => (int) $productStats->low_stock,
                    'featured' => (int) $productStats->featured,
                ],
                'categories' => [
                    'total' => Category::count(),
                    'with_products' => Category::has('products')->count(),
                ],
                'orders' => [
                    'total' => Order::count(),
                    'by_status' => [
                        'pending' => $ordersByStatus['pending'] ?? 0,
                        'confirmed' => $ordersByStatus['confirmed'] ?? 0,
                        'processing' => $ordersByStatus['processing'] ?? 0,
                        'shipped' => $ordersByStatus['shipped'] ?? 0,
                        'delivered' => $ordersByStatus['delivered'] ?? 0,
                        'cancelled' => $ordersByStatus['cancelled'] ?? 0,
                    ],
                    'by_payment_status' => [
                        'pending' => $ordersByPaymentStatus['pending'] ?? 0,
                        'paid' => $ordersByPaymentStatus['paid'] ?? 0,
                        'failed' => $ordersByPaymentStatus['failed'] ?? 0,
                        'refunded' => $ordersByPaymentStatus['refunded'] ?? 0,
                    ],
                ],
                'reviews' => [
                    'total' => (int) $reviewStats->total,
                    'published' => (int) $reviewStats->published,
                    'pending' => (int) $reviewStats->pending,
                    'average_rating' => round((float) ($reviewStats->avg_rating ?? 0), 2),
                ],
                'vendor_requests' => [
                    'pending' => VendorRequest::where('status', 'pending')->count(),
                    'approved' => VendorRequest::where('status', 'approved')->count(),
                    'rejected' => VendorRequest::where('status', 'rejected')->count(),
                ],
            ];
        });
    }

    /**
     * Revenue metrics with period comparisons
     */
    private function getRevenueMetrics(): array
    {
        return Cache::remember('admin.dashboard.revenue', self::CACHE_TTL, function () {
            $now = Carbon::now();

            // Define date ranges
            $today = $now->copy()->startOfDay();
            $yesterday = $now->copy()->subDay()->startOfDay();
            $thisWeekStart = $now->copy()->startOfWeek();
            $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
            $thisMonthStart = $now->copy()->startOfMonth();
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
            $thisYearStart = $now->copy()->startOfYear();

            // Base query for paid orders
            $paidOrders = Order::where('payment_status', 'paid');

            // Get metrics with separate queries (more reliable)
            $totalRevenue = (float) $paidOrders->sum('total');
            $avgOrderValue = (float) $paidOrders->avg('total');

            $todayRevenue = (float) Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $today)
                ->sum('total');

            $yesterdayRevenue = (float) Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$yesterday, $today])
                ->sum('total');

            $thisWeekRevenue = (float) Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisWeekStart)
                ->sum('total');

            $lastWeekRevenue = (float) Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$lastWeekStart, $thisWeekStart])
                ->sum('total');

            $thisMonthRevenue = (float) Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisMonthStart)
                ->sum('total');

            $lastMonthRevenue = (float) Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$lastMonthStart, $thisMonthStart])
                ->sum('total');

            $thisYearRevenue = (float) Order::where('payment_status', 'paid')
                ->where('created_at', '>=', $thisYearStart)
                ->sum('total');

            // Revenue by payment method
            $revenueByMethod = Order::where('payment_status', 'paid')
                ->select('payment_method', DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(fn($item) => [
                    $item->payment_method ?? 'unknown' => [
                        'revenue' => round((float) $item->revenue, 2),
                        'count' => (int) $item->count,
                    ]
                ])
                ->toArray();

            return [
                'total_revenue' => round($totalRevenue, 2),
                'average_order_value' => round($avgOrderValue, 2),
                'today' => [
                    'revenue' => round($todayRevenue, 2),
                    'growth_rate' => $this->calculateGrowthRate($todayRevenue, $yesterdayRevenue),
                    'comparison_label' => 'vs yesterday',
                ],
                'this_week' => [
                    'revenue' => round($thisWeekRevenue, 2),
                    'growth_rate' => $this->calculateGrowthRate($thisWeekRevenue, $lastWeekRevenue),
                    'comparison_label' => 'vs last week',
                ],
                'this_month' => [
                    'revenue' => round($thisMonthRevenue, 2),
                    'growth_rate' => $this->calculateGrowthRate($thisMonthRevenue, $lastMonthRevenue),
                    'comparison_label' => 'vs last month',
                ],
                'this_year' => [
                    'revenue' => round($thisYearRevenue, 2),
                ],
                'revenue_by_payment_method' => $revenueByMethod,
            ];
        });
    }

    /**
     * Order analytics and fulfillment metrics
     */
    private function getOrderAnalytics(): array
    {
        return Cache::remember('admin.dashboard.orders', self::CACHE_TTL, function () {
            $now = Carbon::now();
            $last7Days = $now->copy()->subDays(7);
            $last30Days = $now->copy()->subDays(30);

            // Order completion and processing metrics
            $orderMetrics = Order::selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered_orders,
                AVG(CASE WHEN delivered_at IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, created_at, delivered_at)
                    ELSE NULL END) as avg_processing_hours
            ', ['delivered'])->first();

            $totalOrders = (int) $orderMetrics->total_orders;
            $deliveredOrders = (int) $orderMetrics->delivered_orders;
            $completionRate = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 2) : 0;

            // Order value distribution
            $valueDistribution = [
                '0-50' => Order::whereBetween('total', [0, 50])->count(),
                '51-100' => Order::whereBetween('total', [51, 100])->count(),
                '101-200' => Order::whereBetween('total', [101, 200])->count(),
                '201-500' => Order::whereBetween('total', [201, 500])->count(),
                '500+' => Order::where('total', '>', 500)->count(),
            ];

            // Peak ordering hours
            $peakHours = Order::where('created_at', '>=', $last30Days)
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->mapWithKeys(fn($item) => [
                    sprintf('%02d:00', $item->hour) => (int) $item->count
                ])
                ->toArray();

            return [
                'completion_rate' => $completionRate,
                'average_processing_time_hours' => round((float) ($orderMetrics->avg_processing_hours ?? 0), 1),
                'recent_activity' => [
                    'last_7_days' => Order::where('created_at', '>=', $last7Days)->count(),
                    'last_30_days' => Order::where('created_at', '>=', $last30Days)->count(),
                ],
                'value_distribution' => $valueDistribution,
                'peak_ordering_hours' => $peakHours,
            ];
        });
    }

    /**
     * Product performance and inventory analytics
     */
    private function getProductAnalytics(): array
    {
        return Cache::remember('admin.dashboard.products', self::CACHE_TTL, function () {
            // Top selling products - using actual column names from migration
            $topSellingProducts = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'paid')
                ->select(
                    'products.id',
                    'products.slug',
                    'products.title',
                    'products.image',
                    'products.price',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.subtotal) as total_revenue'),
                    DB::raw('COUNT(DISTINCT order_items.order_id) as order_count')
                )
                ->groupBy('products.id', 'products.slug', 'products.title', 'products.image', 'products.price')
                ->orderByDesc('total_sold')
                ->limit(self::TOP_ITEMS_LIMIT)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'title' => $item->title,
                    'image' => $item->image,
                    'price' => round((float) $item->price, 2),
                    'total_sold' => (int) $item->total_sold,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                    'order_count' => (int) $item->order_count,
                ]);

            // Low stock products with relationships
            $lowStockProducts = Product::with(['category:id,title', 'subcategory:id,title'])
                ->whereBetween('stock', [1, self::LOW_STOCK_THRESHOLD])
                ->orderBy('stock', 'asc')
                ->limit(20)
                ->get(['id', 'slug', 'title', 'image', 'stock', 'price', 'category_id', 'subcategory_id'])
                ->map(fn($product) => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $product->title,
                    'image' => $product->image,
                    'stock' => $product->stock,
                    'price' => $product->price,
                    'category' => $product->category?->title,
                    'subcategory' => $product->subcategory?->title,
                ]);

            // Out of stock by category
            $outOfStockByCategory = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.stock', 0)
                ->select('categories.title as category', DB::raw('COUNT(*) as count'))
                ->groupBy('categories.id', 'categories.title')
                ->orderByDesc('count')
                ->get();

            // Most viewed products
            $mostViewedProducts = Product::orderByDesc('view_count')
                ->limit(self::TOP_ITEMS_LIMIT)
                ->get(['id', 'slug', 'title', 'image', 'view_count', 'sales_count']);

            // Product metrics
            $productMetrics = Product::selectRaw('
                SUM(view_count) as total_views,
                SUM(sales_count) as total_sales,
                AVG(stock) as avg_stock
            ')->first();

            $totalViews = (int) ($productMetrics->total_views ?? 0);
            $totalSales = (int) ($productMetrics->total_sales ?? 0);
            $conversionRate = $totalViews > 0 ? round(($totalSales / $totalViews) * 100, 2) : 0;

            return [
                'top_selling' => $topSellingProducts,
                'low_stock' => $lowStockProducts,
                'out_of_stock_by_category' => $outOfStockByCategory,
                'most_viewed' => $mostViewedProducts,
                'metrics' => [
                    'total_views' => $totalViews,
                    'total_sales' => $totalSales,
                    'conversion_rate' => $conversionRate,
                    'average_stock_level' => round((float) ($productMetrics->avg_stock ?? 0), 2),
                ],
            ];
        });
    }

    /**
     * Vendor performance analytics (multi-vendor marketplace)
     */
    private function getVendorAnalytics(): array
    {
        return Cache::remember('admin.dashboard.vendors', self::CACHE_TTL, function () {
            // Top performing vendors
            $topVendors = DB::table('vendor_products')
                ->join('users', 'vendor_products.vendor_id', '=', 'users.id')
                ->join('order_items', 'vendor_products.product_id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'paid')
                ->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.company_name',
                    DB::raw('COUNT(DISTINCT vendor_products.product_id) as products_count'),
                    DB::raw('SUM(order_items.quantity) as total_sales'),
                    DB::raw('SUM(order_items.subtotal) as total_revenue')
                )
                ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.company_name')
                ->orderByDesc('total_revenue')
                ->limit(self::TOP_ITEMS_LIMIT)
                ->get()
                ->map(fn($vendor) => [
                    'id' => $vendor->id,
                    'name' => trim("{$vendor->first_name} {$vendor->last_name}"),
                    'email' => $vendor->email,
                    'company_name' => $vendor->company_name,
                    'products_count' => (int) $vendor->products_count,
                    'total_sales' => (int) $vendor->total_sales,
                    'total_revenue' => round((float) $vendor->total_revenue, 2),
                ]);

            // Vendor product statistics
            $vendorProductStats = VendorProduct::selectRaw('
                COUNT(*) as total_listings,
                SUM(CASE WHEN is_available = 1 AND stock > 0 THEN 1 ELSE 0 END) as active_listings,
                SUM(stock) as total_stock,
                AVG(price) as average_price
            ')->first();

            return [
                'top_performers' => $topVendors,
                'product_stats' => [
                    'total_listings' => (int) ($vendorProductStats->total_listings ?? 0),
                    'active_listings' => (int) ($vendorProductStats->active_listings ?? 0),
                    'total_stock' => (int) ($vendorProductStats->total_stock ?? 0),
                    'average_price' => round((float) ($vendorProductStats->average_price ?? 0), 2),
                ],
                'requests' => [
                    'pending' => VendorRequest::where('status', 'pending')->count(),
                    'approved_this_month' => VendorRequest::where('status', 'approved')
                        ->whereYear('reviewed_at', Carbon::now()->year)
                        ->whereMonth('reviewed_at', Carbon::now()->month)
                        ->count(),
                ],
            ];
        });
    }

    /**
     * Customer behavior and lifetime value analytics
     */
    private function getCustomerAnalytics(): array
    {
        $last30Days = Carbon::now()->subDays(30);

        // Customer segmentation
        $customerSegments = User::whereHas('roles', fn($q) => $q->where('name', 'user'))
            ->withCount('orders')
            ->get(['id'])
            ->groupBy(
                fn($user) =>
                $user->orders_count === 0 ? 'no_orders' : ($user->orders_count === 1 ? 'new' : 'returning')
            )
            ->map->count();

        // Top customers by lifetime value
        $topCustomers = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.payment_status', 'paid')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total) as lifetime_value'),
                DB::raw('AVG(orders.total) as average_order_value')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('lifetime_value')
            ->limit(self::TOP_ITEMS_LIMIT)
            ->get()
            ->map(fn($customer) => [
                'id' => $customer->id,
                'name' => trim("{$customer->first_name} {$customer->last_name}"),
                'email' => $customer->email,
                'total_orders' => (int) $customer->total_orders,
                'lifetime_value' => round((float) $customer->lifetime_value, 2),
                'average_order_value' => round((float) $customer->average_order_value, 2),
            ]);

        // Customer growth (last 30 days)
        $customerGrowth = User::whereHas('roles', fn($q) => $q->where('name', 'user'))
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
            ]);

        return [
            'segments' => [
                'no_orders' => $customerSegments['no_orders'] ?? 0,
                'new_customers' => $customerSegments['new'] ?? 0,
                'returning_customers' => $customerSegments['returning'] ?? 0,
            ],
            'top_customers' => $topCustomers,
            'growth_last_30_days' => $customerGrowth,
            'new_signups_today' => User::whereHas('roles', fn($q) => $q->where('name', 'user'))
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];
    }

    /**
     * Review and rating analytics
     */
    private function getReviewMetrics(): array
    {
        return Cache::remember('admin.dashboard.reviews', self::CACHE_TTL, function () {
            // Rating distribution
            $ratingDistribution = Review::where('is_published', true)
                ->select('rating', DB::raw('COUNT(*) as count'))
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            // Fill missing ratings with 0
            for ($i = 1; $i <= 5; $i++) {
                $ratingDistribution[$i] = $ratingDistribution[$i] ?? 0;
            }
            ksort($ratingDistribution);

            // Pending reviews for moderation
            $pendingReviews = Review::with([
                'user:id,first_name,last_name,avatar',
                'product:id,slug,title,image'
            ])
                ->where('is_published', false)
                ->latest()
                ->limit(self::RECENT_ITEMS_LIMIT)
                ->get(['id', 'user_id', 'product_id', 'rating', 'comment', 'is_published', 'created_at'])
                ->map(fn($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => trim("{$review->user->first_name} {$review->user->last_name}"),
                        'avatar' => $review->user->avatar,
                    ] : null,
                    'product' => $review->product ? [
                        'id' => $review->product->id,
                        'slug' => $review->product->slug,
                        'title' => $review->product->title,
                        'image' => $review->product->image,
                    ] : null,
                    'created_at' => $review->created_at->toISOString(),
                ]);

            // Most reviewed products - FIXED: Don't use select() with withCount()
            $mostReviewedProducts = Product::withCount('reviews')
                ->having('reviews_count', '>', 0)
                ->orderByDesc('reviews_count')
                ->limit(10)
                ->get(['id', 'slug', 'title', 'image'])
                ->map(fn($product) => [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'title' => $product->title,
                    'image' => $product->image,
                    'reviews_count' => $product->reviews_count,
                ]);

            // Calculate review rate
            $totalPaidOrders = Order::where('payment_status', 'paid')->count();
            $ordersWithReviews = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('reviews', function ($join) {
                    $join->on('order_items.product_id', '=', 'reviews.product_id')
                        ->on('orders.user_id', '=', 'reviews.user_id');
                })
                ->where('orders.payment_status', 'paid')
                ->distinct('orders.id')
                ->count('orders.id');

            $reviewRate = $totalPaidOrders > 0
                ? round(($ordersWithReviews / $totalPaidOrders) * 100, 2)
                : 0;

            return [
                'rating_distribution' => $ratingDistribution,
                'pending_moderation' => $pendingReviews,
                'most_reviewed_products' => $mostReviewedProducts,
                'average_rating' => round(Review::where('is_published', true)->avg('rating') ?? 0, 2),
                'review_rate' => $reviewRate,
            ];
        });
    }

    /**
     * Category performance metrics
     */
    private function getCategoryPerformance(): array
    {
        return Cache::remember('admin.dashboard.categories', self::CACHE_TTL, function () {
            $categoryStats = DB::table('categories')
                ->leftJoin('products', 'categories.id', '=', 'products.category_id')
                ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('orders', function ($join) {
                    $join->on('order_items.order_id', '=', 'orders.id')
                        ->where('orders.payment_status', '=', 'paid');
                })
                ->select(
                    'categories.id',
                    'categories.title',
                    'categories.slug',
                    'categories.image',
                    DB::raw('COUNT(DISTINCT products.id) as products_count'),
                    DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sales'),
                    DB::raw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
                )
                ->groupBy('categories.id', 'categories.title', 'categories.slug', 'categories.image')
                ->orderByDesc('total_revenue')
                ->get()
                ->map(fn($category) => [
                    'id' => $category->id,
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'image' => $category->image,
                    'products_count' => (int) $category->products_count,
                    'total_sales' => (int) $category->total_sales,
                    'total_revenue' => round((float) $category->total_revenue, 2),
                ]);

            return [
                'performance' => $categoryStats,
                'total_categories' => Category::count(),
                'categories_with_products' => Category::has('products')->count(),
            ];
        });
    }

    /**
     * Trend data for charts and graphs
     */
    private function getTrendAnalytics(): array
    {
        return Cache::remember('admin.dashboard.trends', self::CACHE_TTL, function () {
            $last30Days = Carbon::now()->subDays(30);

            // Daily sales trend (last 30 days)
            $dailySales = Order::where('created_at', '>=', $last30Days)
                ->where('payment_status', 'paid')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'orders_count' => (int) $item->orders_count,
                    'revenue' => round((float) $item->revenue, 2),
                ]);

            // Monthly revenue trend (last 12 months)
            $monthlyRevenue = Order::where('created_at', '>=', Carbon::now()->subMonths(12))
                ->where('payment_status', 'paid')
                ->selectRaw('
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    COUNT(*) as orders_count,
                    SUM(total) as revenue
                ')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(fn($item) => [
                    'period' => Carbon::create($item->year, $item->month)->format('M Y'),
                    'year' => (int) $item->year,
                    'month' => (int) $item->month,
                    'orders_count' => (int) $item->orders_count,
                    'revenue' => round((float) $item->revenue, 2),
                ]);

            return [
                'daily_sales_30_days' => $dailySales,
                'monthly_revenue_12_months' => $monthlyRevenue,
            ];
        });
    }

    /**
     * Recent activities for activity feed
     */
    private function getRecentActivities(): array
    {
        // Recent orders
        $recentOrders = Order::with('user:id,first_name,last_name')
            ->latest()
            ->limit(self::RECENT_ITEMS_LIMIT)
            ->get(['id', 'reference', 'user_id', 'total', 'status', 'payment_status', 'created_at'])
            ->map(fn($order) => [
                'id' => $order->id,
                'reference' => $order->reference,
                'user_name' => $order->user
                    ? trim("{$order->user->first_name} {$order->user->last_name}")
                    : 'Guest',
                'total' => $order->total,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at->toISOString(),
            ]);

        // Recent user signups
        $recentSignups = User::whereHas('roles', fn($q) => $q->where('name', 'user'))
            ->latest()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at'])
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => trim("{$user->first_name} {$user->last_name}"),
                'email' => $user->email,
                'created_at' => $user->created_at->toISOString(),
            ]);

        // Recent vendor requests
        $recentVendorRequests = VendorRequest::latest()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'company_name', 'status', 'created_at'])
            ->map(fn($request) => [
                'id' => $request->id,
                'name' => trim("{$request->first_name} {$request->last_name}"),
                'email' => $request->email,
                'company_name' => $request->company_name,
                'status' => $request->status,
                'created_at' => $request->created_at->toISOString(),
            ]);

        return [
            'orders' => $recentOrders,
            'signups' => $recentSignups,
            'vendor_requests' => $recentVendorRequests,
        ];
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Calculate percentage growth rate
     */
    private function calculateGrowthRate(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Clear all dashboard caches
     */
    public function clearDashboardCache(): void
    {
        $cacheKeys = [
            'admin.dashboard.overview',
            'admin.dashboard.revenue',
            'admin.dashboard.orders',
            'admin.dashboard.products',
            'admin.dashboard.vendors',
            'admin.dashboard.categories',
            'admin.dashboard.reviews',
            'admin.dashboard.trends',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
