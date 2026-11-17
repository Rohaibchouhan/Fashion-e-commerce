@extends('admin.layouts.app')

@section('title', 'Orders')
@section('page_title', 'Customer Orders Management')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200">
        <form method="GET" class="flex flex-wrap items-center gap-3 w-full text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, tracking, customer..." class="px-3 py-2 rounded-lg border border-slate-300 w-56 focus:outline-none focus:ring-1 focus:ring-slate-900">
            
            <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                <option value="all">All Fulfillment Statuses</option>
                @foreach($statuses as $st)
                <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>

            <select name="payment_status" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                <option value="all">All Payment Statuses</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>

            <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-lg font-medium">Filter</button>
            @if(request()->hasAny(['search', 'status', 'payment_status']))
            <a href="{{ route('admin.orders') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Total ($)</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Fulfillment Status</th>
                        <th class="px-4 py-3">Carrier / Tracking</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-bold text-slate-900 font-mono">
                            {{ $order->order_number }}
                            @if($order->is_test)
                            <span class="ml-1 px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-bold rounded">TEST</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $order->customer->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $order->customer->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $order->items->sum('quantity') }} items</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">${{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $order->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ strtoupper($order->payment_status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-800">
                                {{ $order->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <div>{{ $order->carrier ?? 'Unassigned' }}</div>
                            <div class="text-[10px] font-mono text-slate-400 truncate max-w-[120px]">{{ $order->tracking_number ?? 'Pending' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $order->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded font-medium text-xs transition">
                                Manage
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-400">No orders matching query.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection

