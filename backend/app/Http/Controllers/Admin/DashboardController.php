<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // Revenue calculations
        $totalRevenue = (float) Order::where('payment_status', PaymentStatus::PAID)->sum('total');
        $todayRevenue = (float) Order::where('payment_status', PaymentStatus::PAID)
            ->whereDate('created_at', $now->toDateString())
            ->sum('total');
        $monthlyRevenue = (float) Order::where('payment_status', PaymentStatus::PAID)
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total');

        // Order count stats
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', [OrderStatus::PLACED, OrderStatus::CONFIRMED])->count();
        $processingOrders = Order::where('status', OrderStatus::PROCESSING)->count();
        $dispatchedOrders = Order::whereIn('status', [OrderStatus::DISPATCHED, OrderStatus::IN_TRANSIT, OrderStatus::OUT_FOR_DELIVERY])->count();
        $deliveredOrders = Order::where('status', OrderStatus::DELIVERED)->count();
        $cancelledOrders = Order::where('status', OrderStatus::CANCELLED)->count();

        // Customers
        $totalCustomers = User::whereHas('roles', fn ($q) => $q->where('slug', 'customer'))->count();
        $newCustomers = User::whereHas('roles', fn ($q) => $q->where('slug', 'customer'))
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        // Best-selling products
        $bestSellingProducts = Product::with(['images', 'category'])
            ->where('is_bestseller', true)
            ->orWhere('review_count', '>', 50)
            ->orderBy('review_count', 'desc')
            ->limit(5)
            ->get();

        // Low-stock products & variants
        $lowStockItems = Inventory::with(['product.images', 'variant'])
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->orderBy('quantity', 'asc')
            ->limit(6)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['customer', 'items'])
            ->latest()
            ->limit(8)
            ->get();

        // Pending returns & refunds
        $pendingReturns = ReturnRequest::whereIn('status', [ReturnStatus::REQUESTED, ReturnStatus::UNDER_REVIEW])->count();
        $pendingRefunds = Refund::where('status', 'pending')->count();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'todayRevenue',
            'monthlyRevenue',
            'totalOrders',
            'pendingOrders',
            'processingOrders',
            'dispatchedOrders',
            'deliveredOrders',
            'cancelledOrders',
            'totalCustomers',
            'newCustomers',
            'bestSellingProducts',
            'lowStockItems',
            'recentOrders',
            'pendingReturns',
            'pendingRefunds'
        ));
    }
}

