<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard stats and recent lists.
     */
    public function index(): \Illuminate\Http\JsonResponse|\Illuminate\View\View
    {
        $data = Cache::remember('admin_dashboard_stats', 300, function () {
            // Define time windows
            $now = now();
            $sevenDaysAgo = now()->subDays(7);
            $fourteenDaysAgo = now()->subDays(14);

            // 1. Orders Stats (All-time + Week-over-Week Change)
            $totalOrders = Order::count();
            $currentWeekOrders = Order::where('created_at', '>=', $sevenDaysAgo)->count();
            $prevWeekOrders = Order::whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->count();
            $ordersChange = $this->calculatePercentageChange($currentWeekOrders, $prevWeekOrders);

            // 2. Revenue Stats (All-time + Week-over-Week Change)
            $totalRevenue = Order::where('payment_status', 'paid')
                ->orWhere('payment_method', 'cod') // Include COD as prospective revenue
                ->sum('total');
            $currentWeekRevenue = Order::where('created_at', '>=', $sevenDaysAgo)->sum('total');
            $prevWeekRevenue = Order::whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->sum('total');
            $revenueChange = $this->calculatePercentageChange($currentWeekRevenue, $prevWeekRevenue);

            // 3. Customers Stats (All-time + Week-over-Week Change)
            $totalCustomers = User::where('role', 'customer')->count();
            $currentWeekCustomers = User::where('role', 'customer')->where('created_at', '>=', $sevenDaysAgo)->count();
            $prevWeekCustomers = User::where('role', 'customer')->whereBetween('created_at', [$fourteenDaysAgo, $sevenDaysAgo])->count();
            $customersChange = $this->calculatePercentageChange($currentWeekCustomers, $prevWeekCustomers);

            // 4. Recent Products (with stock info & low-stock indicator)
            $recentProducts = Product::with(['category', 'sizes'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    $totalStock = $product->sizes->sum('stock');
                    return [
                        'id' => $product->id,
                        'name_ar' => $product->name,
                        'category' => $product->category->name ?? 'بدون تصنيف',
                        'total_stock' => $totalStock,
                        'is_low_stock' => $totalStock < 5,
                        'sizes' => $product->sizes->map(fn($size) => [
                            'label' => $size->name,
                            'stock' => $size->stock,
                            'price' => $size->price,
                            'formatted_price' => format_money($size->price),
                        ]),
                    ];
                });

            // 5. Recent Incoming Orders (with status badges)
            $recentOrders = Order::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get()
                ->map(function ($order) {
                    $statusLabel = $order->status instanceof \App\Enums\OrderStatus 
                        ? $order->status->labelAr() 
                        : (\App\Enums\OrderStatus::tryFrom((string)$order->status)?->labelAr() ?? 'قيد المعالجة');

                    $paymentMethodLabel = match ($order->payment_method?->value ?? (string) $order->payment_method) {
                        'sham_cash' => 'شام كاش',
                        default     => 'عند الاستلام',
                    };

                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->recipient_name ?? $order->user?->name ?? 'زبون المتجر',
                        'total' => $order->total,
                        'formatted_total' => format_money($order->total),
                        'status' => $order->status?->value ?? (string) $order->status,
                        'status_label' => $statusLabel,
                        'payment_status' => $order->payment_status?->value ?? (string) $order->payment_status,
                        'payment_method' => $order->payment_method?->value ?? (string) $order->payment_method,
                        'payment_method_label' => $paymentMethodLabel,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                    ];
                });

            return [
                'stats' => [
                    'orders' => [
                        'total' => $totalOrders,
                        'change_percent' => $ordersChange,
                    ],
                    'revenue' => [
                        'total' => (int) $totalRevenue,
                        'formatted_total' => format_money((int) $totalRevenue),
                        'change_percent' => $revenueChange,
                    ],
                    'customers' => [
                        'total' => $totalCustomers,
                        'change_percent' => $customersChange,
                    ],
                ],
                'recentProducts' => $recentProducts,
                'recentOrders' => $recentOrders,
            ];
        });

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($data);
        }

        return view('admin.dashboard', $data);
    }

    /**
     * Helper to calculate percentage change week-over-week.
     */
    private function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
