@extends('admin.layouts.app')

@section('title', 'Order ' . $order->order_number)
@section('page_title', 'Order Details: ' . $order->order_number)

@section('content')
<div class="space-y-6 text-xs">
    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.orders') }}" class="text-slate-400 hover:text-slate-700">&larr; Back to Orders</a>
            <span class="text-slate-300">|</span>
            <span class="font-bold text-slate-900 text-sm">{{ $order->order_number }}</span>
            @if($order->is_test)
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                TEST / SANDBOX
            </span>
            @endif
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-900 text-white">
                {{ $order->status->label() }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $order->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700' }}">
                PAYMENT: {{ strtoupper($order->payment_status->value) }}
            </span>
        </div>

        <div class="flex items-center space-x-3">
            @if($order->status->value !== 'cancelled')
            <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Cancel this order and restore product stock?')">
                @csrf
                <input type="hidden" name="reason" value="Administrative cancellation requested">
                <button type="submit" class="px-3 py-1.5 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-medium transition">
                    Cancel Order
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Order Items & Shipping/Billing -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Order Items -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                    Order Items ({{ $order->items->count() }})
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <img src="{{ $item->image_url ?? 'https://placehold.co/80' }}" class="w-12 h-12 object-cover rounded bg-slate-100 flex-shrink-0">
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ $item->product_name }}</div>
                                <div class="text-[11px] text-slate-500">
                                    SKU: <span class="font-mono">{{ $item->product_sku }}</span> &bull; 
                                    Size: <span class="font-semibold text-slate-700">{{ $item->selected_size ?? 'N/A' }}</span> &bull; 
                                    Color: <span class="font-semibold text-slate-700">{{ $item->selected_color ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-slate-900 text-sm">${{ number_format($item->subtotal, 2) }}</div>
                            <div class="text-slate-400 text-[11px]">${{ number_format($item->unit_price, 2) }} &times; {{ $item->quantity }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Financial Summary Box -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="flex justify-between text-emerald-700 font-medium">
                        <span>Discount Coupon ({{ $order->coupon_code }})</span>
                        <span>-${{ number_format($order->discount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping ({{ $order->carrier }})</span>
                        <span>${{ number_format($order->shipping, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Sales Tax</span>
                        <span>${{ number_format($order->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-900 font-bold text-sm pt-2 border-t border-slate-200">
                        <span>Grand Total</span>
                        <span>${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Addresses & Payment Snapshot -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
                        <i class="fa-solid fa-location-dot text-slate-400"></i>
                        <span>Shipping Destination</span>
                    </h4>
                    <p class="text-slate-700 leading-relaxed">
                        <strong>{{ $order->shipping_address['firstName'] ?? $order->shipping_address['first_name'] ?? '' }} {{ $order->shipping_address['lastName'] ?? $order->shipping_address['last_name'] ?? '' }}</strong><br>
                        {{ $order->shipping_address['street'] ?? '' }} {{ $order->shipping_address['apartment'] ?? '' }}<br>
                        {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['zipCode'] ?? $order->shipping_address['zip_code'] ?? '' }}<br>
                        {{ $order->shipping_address['country'] ?? '' }}<br>
                        Phone: {{ $order->shipping_address['phone'] ?? $order->customer->phone ?? 'N/A' }}
                    </p>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
                        <i class="fa-solid fa-credit-card text-slate-400"></i>
                        <span>Payment Snapshot</span>
                    </h4>
                    <p class="text-slate-700 leading-relaxed">
                        <strong>Method:</strong> {{ $order->payment_method }}<br>
                        <strong>Last 4:</strong> **** **** **** {{ $order->payment_last4 ?? '8821' }}<br>
                        <strong>Status:</strong> {{ strtoupper($order->payment_status->value) }}<br>
                        <strong>Carrier:</strong> {{ $order->carrier }}<br>
                        <strong>Tracking #:</strong> <span class="font-mono">{{ $order->tracking_number ?? 'Pending Allocation' }}</span>
                    </p>
                </div>
            </div>

            <!-- Order Timeline History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h4 class="font-bold text-slate-900 mb-4">Tracking & Status Progression Timeline</h4>
                <div class="space-y-4">
                    @foreach($order->statusHistories as $history)
                    <div class="flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs">{{ $history->label ?? $history->new_status }}</span>
                                <span class="text-[11px] text-slate-400">{{ $history->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <p class="text-slate-600 mt-0.5">{{ $history->description }}</p>
                            @if($history->location)
                            <p class="text-slate-400 text-[11px] mt-0.5"><i class="fa-solid fa-location-crosshairs"></i> Location: {{ $history->location }}</p>
                            @endif
                            @if($history->updatedBy)
                            <p class="text-slate-400 text-[10px] mt-0.5">Updated by: {{ $history->updatedBy->name }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Status Update Form -->
        <div class="space-y-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-slate-900 text-sm mb-4">Advance Order Status</h3>
                <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">New Order Status *</label>
                        <select name="status" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                            @foreach($statuses as $st)
                            <option value="{{ $st->value }}" {{ $order->status->value === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Carrier Name</label>
                        <input type="text" name="carrier" value="{{ old('carrier', $order->carrier) }}" placeholder="e.g. FedEx Express, Standard Ground" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tracking Number</label>
                        <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number === 'Pending Allocation' ? '' : $order->tracking_number) }}" placeholder="e.g. 1Z9999999999999999" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Checkpoint Location (Optional)</label>
                        <input type="text" name="location" placeholder="e.g. Newark Distribution Hub, NJ" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Notes / Update Description</label>
                        <textarea name="notes" rows="2" placeholder="Customer-facing note..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition text-xs">
                        Update Status & Notify Customer
                    </button>
                </form>
            </div>

            <!-- Customer Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h4 class="font-bold text-slate-900 mb-3">Customer Information</h4>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-800 text-sm">
                        {{ substr($order->customer->name, 0, 2) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900">{{ $order->customer->name }}</div>
                        <div class="text-slate-500 text-[11px]">{{ $order->customer->email }}</div>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-100 space-y-1 text-slate-600">
                    <div><strong>Phone:</strong> {{ $order->customer->phone ?? 'None' }}</div>
                    <div><strong>Member Since:</strong> {{ $order->customer->created_at->format('M Y') }}</div>
                    <div><strong>Lifetime Orders:</strong> {{ $order->customer->orders->count() }}</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

