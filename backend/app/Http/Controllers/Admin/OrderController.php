<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items', 'payments']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'real') {
                $query->real();
            } elseif ($request->type === 'test') {
                $query->test();
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                  ->orWhere('tracking_number', 'like', "%{$term}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            });
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'total_high' => $query->orderByDesc('total'),
            'total_low' => $query->orderBy('total'),
            default => $query->latest(),
        };

        $orders = $query->paginate(15)->withQueryString();
        $statuses = OrderStatus::cases();

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load([
            'customer' => fn ($q) => $q->withCount('orders'),
            'items.product',
            'items.variant',
            'statusHistories.updatedBy',
            'payments',
            'returns.items',
            'refunds',
            'shipments.events',
        ]);
        $statuses = OrderStatus::cases();

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'tracking_number' => 'nullable|string|max:100',
            'carrier' => 'nullable|string|max:100',
            'estimated_delivery' => 'nullable|date',
        ]);

        try {
            $this->orderService->updateOrderStatus(
                $order,
                $validated['status'],
                $validated['location'] ?? null,
                $validated['notes'] ?? null,
                auth()->id(),
                $validated['tracking_number'] ?? null,
                $validated['carrier'] ?? null,
                $validated['estimated_delivery'] ?? null
            );

            ActivityLog::log('order.status_updated', "Updated Order #{$order->order_number} to {$validated['status']}", $order);

            return back()->with('success', "Order #{$order->order_number} status updated to {$validated['status']}.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->orderService->cancelOrder($order, $validated['reason'], auth()->user());

            ActivityLog::log('order.cancelled', "Cancelled Order #{$order->order_number}. Reason: {$validated['reason']}", $order);

            return back()->with('success', "Order #{$order->order_number} cancelled and stock restored.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

