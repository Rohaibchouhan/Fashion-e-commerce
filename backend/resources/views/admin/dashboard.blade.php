@extends('admin.layouts.app')

@section('title', 'Overview')
@section('page_title', 'Business Intelligence & Operations Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Top KPI Cards: Revenue -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>TOTAL REVENUE (LIFETIME)</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><i class="fa-solid fa-dollar-sign"></i></span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">${{ number_format($totalRevenue, 2) }}</div>
            <div class="mt-1 text-xs text-emerald-600 flex items-center space-x-1">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>All captured payments verified</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>MONTHLY REVENUE</span>
                <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><i class="fa-solid fa-calendar-days"></i></span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">${{ number_format($monthlyRevenue, 2) }}</div>
            <div class="mt-1 text-xs text-slate-500">Current calendar month</div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>TODAY'S REVENUE</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg"><i class="fa-solid fa-sun"></i></span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">${{ number_format($todayRevenue, 2) }}</div>
            <div class="mt-1 text-xs text-slate-500">Real-time daily capture</div>
        </div>
    </div>

    <!-- Order Workflow Statistics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-lg border border-slate-200 text-center">
            <div class="text-[11px] font-bold text-slate-400 uppercase">Total Orders</div>
            <div class="text-xl font-bold text-slate-900 mt-1">{{ $totalOrders }}</div>
        </div>
        <div class="bg-amber-50/60 p-4 rounded-lg border border-amber-200 text-center">
            <div class="text-[11px] font-bold text-amber-700 uppercase">Pending Review</div>
            <div class="text-xl font-bold text-amber-800 mt-1">{{ $pendingOrders }}</div>
        </div>
        <div class="bg-blue-50/60 p-4 rounded-lg border border-blue-200 text-center">
            <div class="text-[11px] font-bold text-blue-700 uppercase">Processing</div>
            <div class="text-xl font-bold text-blue-800 mt-1">{{ $processingOrders }}</div>
        </div>
        <div class="bg-purple-50/60 p-4 rounded-lg border border-purple-200 text-center">
            <div class="text-[11px] font-bold text-purple-700 uppercase">Dispatched</div>
            <div class="text-xl font-bold text-purple-800 mt-1">{{ $dispatchedOrders }}</div>
        </div>
        <div class="bg-emerald-50/60 p-4 rounded-lg border border-emerald-200 text-center">
            <div class="text-[11px] font-bold text-emerald-700 uppercase">Delivered</div>
            <div class="text-xl font-bold text-emerald-800 mt-1">{{ $deliveredOrders }}</div>
        </div>
        <div class="bg-rose-50/60 p-4 rounded-lg border border-rose-200 text-center">
            <div class="text-[11px] font-bold text-rose-700 uppercase">Cancelled</div>
            <div class="text-xl font-bold text-rose-800 mt-1">{{ $cancelledOrders }}</div>
        </div>
    </div>

    <!-- Alert Banners for Customer Support Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Pending Return Requests</div>
                <div class="text-lg font-bold text-slate-900 mt-1">{{ $pendingReturns }} awaiting action</div>
            </div>
            <a href="{{ route('admin.returns') }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium transition">
                Review RMAs
            </a>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Pending Refunds</div>
                <div class="text-lg font-bold text-slate-900 mt-1">{{ $pendingRefunds }} queued for payout</div>
            </div>
            <a href="{{ route('admin.refunds') }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium transition">
                Process
            </a>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-center justify-between">
            <div>
                <div class="text-xs text-slate-500 font-medium">Customer Community</div>
                <div class="text-lg font-bold text-slate-900 mt-1">{{ $totalCustomers }} shoppers ({{ $newCustomers }} new)</div>
            </div>
            <a href="{{ route('admin.customers') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">
                View Profiles
            </a>
        </div>
    </div>

    <!-- Two-Column Section: Recent Orders & Inventory Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Recent Orders (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm">Recent Customer Orders</h3>
                <a href="{{ route('admin.orders') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">View All Orders &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Order #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Fulfillment</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $order->customer->name }}</td>
                            <td class="px-4 py-3 font-medium">${{ number_format($order->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $order->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ strtoupper($order->payment_status->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $order->created_at->format('M d, H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-slate-900 hover:underline font-medium">Details</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">No orders logged yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Alerts & Best Sellers (1 Column) -->
        <div class="space-y-6">
            <!-- Low-Stock Alerts -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-amber-50/40">
                    <h3 class="font-bold text-amber-900 text-xs flex items-center space-x-1.5">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                        <span>Low Inventory Alerts</span>
                    </h3>
                    <a href="{{ route('admin.inventory') }}" class="text-[11px] font-medium text-amber-800 hover:underline">Manage Stock</a>
                </div>

                <div class="p-3 divide-y divide-slate-100">
                    @forelse($lowStockItems as $inv)
                    <div class="py-2.5 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2.5 truncate">
                            <img src="{{ $inv->product->images->first()?->url ?? '' }}" class="w-8 h-8 rounded object-cover flex-shrink-0 bg-slate-100">
                            <div class="truncate">
                                <div class="font-medium text-slate-900 truncate">{{ $inv->product->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $inv->variant ? "{$inv->variant->color} / {$inv->variant->size}" : "General SKU" }}</div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $inv->quantity <= 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ $inv->quantity }} left
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="py-6 text-center text-xs text-slate-400">All warehouse stock healthy.</div>
                    @endforelse
                </div>
            </div>

            <!-- Best Sellers Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-xs">Top Performing Garments</h3>
                    <a href="{{ route('admin.products') }}" class="text-[11px] font-medium text-slate-500 hover:text-slate-900">Catalog</a>
                </div>
                <div class="p-3 divide-y divide-slate-100">
                    @foreach($bestSellingProducts as $bp)
                    <div class="py-2 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2 truncate">
                            <img src="{{ $bp->images->first()?->url ?? '' }}" class="w-8 h-8 rounded object-cover flex-shrink-0">
                            <div class="truncate">
                                <div class="font-medium text-slate-800 truncate">{{ $bp->name }}</div>
                                <div class="text-[10px] text-slate-400">${{ number_format($bp->regular_price, 2) }}</div>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 text-[11px] text-slate-500">
                            <i class="fa-solid fa-star text-amber-400 text-[10px]"></i> {{ $bp->rating }} ({{ $bp->review_count }})
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

