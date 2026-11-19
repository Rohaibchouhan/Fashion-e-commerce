@extends('admin.layouts.app')

@section('title', 'Order ' . $order->order_number)
@section('page_title', 'Order Details: ' . $order->order_number)

@section('content')
<div class="space-y-6 text-xs">
    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.orders') }}" class="text-slate-500 hover:text-slate-800 font-medium">&larr; Back to Orders</a>
            <span class="text-slate-300">|</span>
            <span class="font-bold text-slate-900 text-sm font-mono">{{ $order->order_number }}</span>
            <span class="text-slate-400 text-xs">Placed on {{ $order->created_at->format('M d, Y H:i') }}</span>
            @if($order->is_test)
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                TEST / SANDBOX
            </span>
            @endif
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-900 text-white">
                {{ $order->status->label() }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $order->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                PAYMENT: {{ strtoupper($order->payment_status->value) }}
            </span>
        </div>

        <div class="flex items-center space-x-3">
            @if(!in_array($order->status->value, ['cancelled', 'dispatched', 'in_transit', 'out_for_delivery', 'delivered']))
            <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST" onsubmit="const r = prompt('Please specify a cancellation reason:', 'Administrative cancellation requested'); if (!r) return false; this.querySelector('input[name=reason]').value = r; return true;">
                @csrf
                <input type="hidden" name="reason" value="Administrative cancellation requested">
                <button type="submit" class="px-3 py-1.5 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-medium transition">
                    Cancel Order
                </button>
            </form>
            @elseif($order->status->value === 'cancelled')
            <span class="px-3 py-1 rounded bg-rose-50 text-rose-700 font-medium border border-rose-200">
                Order Cancelled ({{ $order->cancellation_reason ?? 'No reason recorded' }})
            </span>
            @endif
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Order Items, Addresses, Payments, Shipments & Returns -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Order Items -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 font-bold text-slate-900 flex justify-between items-center">
                    <span>Order Items ({{ $order->items->count() }})</span>
                    <span class="text-xs text-slate-400 font-normal">Historical snapshot preserved</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <img src="{{ $item->image_url ?? 'https://placehold.co/80' }}" class="w-12 h-12 object-cover rounded bg-slate-100 flex-shrink-0" alt="{{ $item->product_name }}">
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
                        <span>Shipping ({{ $order->carrier ?? 'Standard' }})</span>
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

            <!-- Address Snapshots (Shipping & Billing) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
                        <i class="fa-solid fa-location-dot text-slate-400"></i>
                        <span>Shipping Address (Historical Snapshot)</span>
                    </h4>
                    <p class="text-slate-700 leading-relaxed">
                        <strong>{{ $order->shipping_address['firstName'] ?? $order->shipping_address['first_name'] ?? '' }} {{ $order->shipping_address['lastName'] ?? $order->shipping_address['last_name'] ?? '' }}</strong><br>
                        {{ $order->shipping_address['street'] ?? $order->shipping_address['address'] ?? '' }} {{ $order->shipping_address['apartment'] ?? '' }}<br>
                        {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} {{ $order->shipping_address['zipCode'] ?? $order->shipping_address['zip_code'] ?? $order->shipping_address['zip'] ?? '' }}<br>
                        {{ $order->shipping_address['country'] ?? '' }}<br>
                        <span class="text-slate-500 text-[11px]">Phone: {{ $order->shipping_address['phone'] ?? $order->customer->phone ?? 'N/A' }}</span>
                    </p>
                </div>

                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <h4 class="font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
                        <i class="fa-solid fa-file-invoice text-slate-400"></i>
                        <span>Billing Address (Historical Snapshot)</span>
                    </h4>
                    <p class="text-slate-700 leading-relaxed">
                        <strong>{{ $order->billing_address['firstName'] ?? $order->billing_address['first_name'] ?? '' }} {{ $order->billing_address['lastName'] ?? $order->billing_address['last_name'] ?? '' }}</strong><br>
                        {{ $order->billing_address['street'] ?? $order->billing_address['address'] ?? '' }} {{ $order->billing_address['apartment'] ?? '' }}<br>
                        {{ $order->billing_address['city'] ?? '' }}, {{ $order->billing_address['state'] ?? '' }} {{ $order->billing_address['zipCode'] ?? $order->billing_address['zip_code'] ?? $order->billing_address['zip'] ?? '' }}<br>
                        {{ $order->billing_address['country'] ?? '' }}<br>
                        <span class="text-slate-500 text-[11px]">Phone: {{ $order->billing_address['phone'] ?? $order->customer->phone ?? 'N/A' }}</span>
                    </p>
                </div>
            </div>

            <!-- Payment Details & Transactions -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                        <i class="fa-solid fa-credit-card text-slate-400"></i>
                        <span>Payment Records</span>
                    </h4>
                    <span class="text-[11px] font-semibold text-slate-600">Method: {{ $order->payment_method }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-slate-600">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Payment Status</div>
                        <div class="font-bold text-slate-900 uppercase">{{ $order->payment_status->value }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Card Ending</div>
                        <div class="font-bold text-slate-900">**** **** **** {{ $order->payment_last4 ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Fulfillment Status</div>
                        <div class="font-bold text-slate-900 capitalize">{{ str_replace('_', ' ', $order->fulfillment_status->value) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Total Amount</div>
                        <div class="font-bold text-slate-900">${{ number_format($order->total, 2) }}</div>
                    </div>
                </div>

                @if($order->payments->isNotEmpty())
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <div class="text-[11px] font-bold text-slate-700 mb-2">Verified Gateway Transactions</div>
                    <div class="space-y-1.5">
                        @foreach($order->payments as $payment)
                        <div class="flex items-center justify-between p-2 rounded bg-slate-50 border border-slate-100 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-slate-800 font-bold">{{ $payment->transaction_id ?? $payment->id }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $payment->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }} uppercase">
                                    {{ $payment->status }}
                                </span>
                                @if($payment->is_test)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">TEST</span>
                                @endif
                            </div>
                            <div class="text-right text-slate-600">
                                <span class="font-bold">${{ number_format($payment->amount, 2) }} {{ $payment->currency }}</span> &bull;
                                <span class="text-slate-400 text-[11px]">{{ $payment->gateway ?? 'Gateway' }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Shipment & Dispatch Details -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="font-bold text-slate-900 flex items-center space-x-1.5">
                        <i class="fa-solid fa-truck-fast text-slate-400"></i>
                        <span>Shipment & Dispatch Management</span>
                    </h4>
                    @if($order->tracking_url)
                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline font-semibold flex items-center space-x-1">
                        <span>Carrier Tracking Page</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-slate-600">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Assigned Carrier</div>
                        <div class="font-bold text-slate-900">{{ $order->carrier ?? 'Unassigned' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Tracking Number</div>
                        <div class="font-mono font-bold text-slate-900 truncate">{{ $order->tracking_number ?? 'Pending Allocation' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Estimated Delivery</div>
                        <div class="font-bold text-slate-900">{{ $order->estimated_delivery ? $order->estimated_delivery->format('M d, Y') : 'Pending' }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase">Dispatch Status</div>
                        <div class="font-bold text-slate-900">{{ $order->status->label() }}</div>
                    </div>
                </div>

                @if($order->shipments->isNotEmpty())
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <div class="text-[11px] font-bold text-slate-700 mb-2">Shipment Records</div>
                    <div class="space-y-2">
                        @foreach($order->shipments as $shipment)
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $shipment->carrier }} - <span class="font-mono">{{ $shipment->tracking_number }}</span></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 text-slate-800 uppercase">{{ $shipment->status }}</span>
                            </div>
                            @if($shipment->shipped_at)
                            <div class="text-slate-500 text-[11px]">Dispatched at: {{ $shipment->shipped_at->format('M d, Y H:i') }}</div>
                            @endif

                            @if($shipment->events->isNotEmpty())
                            <div class="pl-3 border-l-2 border-slate-300 space-y-1 mt-2">
                                @foreach($shipment->events as $ev)
                                <div class="text-[11px] text-slate-600">
                                    <span class="font-semibold">{{ $ev->status }}</span> - {{ $ev->description }}
                                    @if($ev->location) <span class="text-slate-400">({{ $ev->location }})</span> @endif
                                    <span class="text-slate-400 text-[10px] ml-1">{{ $ev->occurred_at ? \Carbon\Carbon::parse($ev->occurred_at)->format('M d, H:i') : '' }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Returns & Refunds Section (If Available) -->
            @if($order->returns->isNotEmpty() || $order->refunds->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                <h4 class="font-bold text-slate-900 flex items-center space-x-1.5 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-rotate-left text-slate-400"></i>
                    <span>Returns & Refunds Integration</span>
                </h4>
                @if($order->returns->isNotEmpty())
                <div class="space-y-2">
                    <div class="text-[11px] font-bold text-slate-700">Return Requests</div>
                    @foreach($order->returns as $ret)
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-100 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 font-mono">{{ $ret->return_number ?? '#' . $ret->id }}</span>
                            <span class="ml-1 text-slate-500">Reason: {{ $ret->reason }}</span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 uppercase">{{ $ret->status }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($order->refunds->isNotEmpty())
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <div class="text-[11px] font-bold text-slate-700">Issued Refunds</div>
                    @foreach($order->refunds as $ref)
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-100 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 font-mono">{{ $ref->refund_number ?? '#' . $ref->id }}</span>
                            <span class="ml-1 text-slate-500">${{ number_format($ref->amount, 2) }} via {{ $ref->gateway ?? 'Gateway' }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($ref->status === 'processed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 uppercase">{{ $ref->status }}</span>
                            @elseif($ref->status === 'failed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 uppercase" title="{{ $ref->notes }}">{{ $ref->status }}</span>
                                @if(auth()->user()->hasPermission('refunds.retry') || auth()->user()->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.refunds.retry', $ref->id) }}" class="inline" onsubmit="return confirm('Retry refund of ${{ number_format($ref->amount, 2) }}?');">
                                    @csrf
                                    <button type="submit" class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium text-[10px] transition inline-flex items-center space-x-1">
                                        <i class="fa-solid fa-rotate-right"></i>
                                        <span>Retry</span>
                                    </button>
                                </form>
                                @endif
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 uppercase">{{ $ref->status }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            <!-- Order Timeline History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h4 class="font-bold text-slate-900 mb-4 flex items-center space-x-1.5">
                    <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
                    <span>Tracking & Status Progression Timeline</span>
                </h4>
                <div class="space-y-4">
                    @forelse($order->statusHistories as $history)
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
                    @empty
                    <div class="text-slate-400 text-xs">No status transitions recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Status Update Form & Customer Card -->
        <div class="space-y-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-slate-900 text-sm mb-4">Advance Order Status</h3>
                
                @php
                    $allowedTransitions = $order->status->allowedTransitions();
                @endphp

                @if(count($allowedTransitions) > 0)
                <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">New Order Status *</label>
                        <select name="status" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <option value="{{ $order->status->value }}" selected>{{ $order->status->label() }} (Current)</option>
                            @foreach($allowedTransitions as $next)
                            <option value="{{ $next->value }}">
                                &rarr; {{ $next->label() }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">State transitions are strictly enforced to preserve lifecycle integrity.</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Carrier Name</label>
                        <input type="text" name="carrier" value="{{ old('carrier', $order->carrier) }}" placeholder="e.g. FedEx, UPS, USPS, DHL" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tracking Number</label>
                        <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number === 'Pending Allocation' ? '' : $order->tracking_number) }}" placeholder="e.g. 1Z9999999999999999" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none font-mono">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Estimated Delivery Date</label>
                        <input type="date" name="estimated_delivery" value="{{ old('estimated_delivery', $order->estimated_delivery ? $order->estimated_delivery->toDateString() : '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Checkpoint Location (Optional)</label>
                        <input type="text" name="location" placeholder="e.g. Newark Distribution Hub, NJ" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Notes / Update Description</label>
                        <textarea name="notes" rows="2" placeholder="Customer-facing note or dispatch memo..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition text-xs">
                        Update Status & Notify Customer
                    </button>
                </form>
                @else
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 text-xs space-y-1">
                    <div class="font-bold text-slate-900">Terminal State Reached</div>
                    <p>Order is in terminal status (<strong class="capitalize">{{ $order->status->label() }}</strong>). Further administrative status changes are locked.</p>
                </div>
                @endif
            </div>

            <!-- Customer Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h4 class="font-bold text-slate-900 mb-3">Customer Information</h4>
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-800 text-sm">
                        {{ substr($order->customer->name ?? 'User', 0, 2) }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900">{{ $order->customer->name ?? 'Customer' }}</div>
                        <div class="text-slate-500 text-[11px]">{{ $order->customer->email ?? 'No email' }}</div>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-100 space-y-1 text-slate-600">
                    <div><strong>Phone:</strong> {{ $order->customer->phone ?? 'None' }}</div>
                    <div><strong>Member Since:</strong> {{ $order->customer->created_at ? $order->customer->created_at->format('M Y') : 'N/A' }}</div>
                    <div><strong>Lifetime Orders:</strong> {{ $order->customer->orders_count ?? ($order->customer ? $order->customer->orders()->count() : 1) }}</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
