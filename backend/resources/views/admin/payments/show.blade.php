@extends('admin.layouts.app')

@section('title', 'Payment #' . $payment->id)
@section('page_title', 'Payment Details & Gateway Audit Trail')

@section('content')
<div class="space-y-6 text-xs">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.payments', ['tab' => $payment->is_test ? 'test' : 'payments']) }}"
               class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Payments</span>
            </a>
            <h2 class="text-base font-bold text-slate-900">
                Payment #PAY-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}
            </h2>
            @if($payment->is_test)
            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded uppercase">
                TEST / SANDBOX
            </span>
            @endif
        </div>

        <div>
            @php
                $statusVal = is_object($payment->status) ? $payment->status->value : $payment->status;
            @endphp
            @if($statusVal === 'paid')
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>CONFIRMED PAID</span>
                </span>
            @elseif($statusVal === 'pending')
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center space-x-1">
                    <i class="fa-solid fa-clock"></i>
                    <span>PENDING AUTHORIZATION</span>
                </span>
            @elseif($statusVal === 'failed')
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 flex items-center space-x-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span>FAILED / DECLINED</span>
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                    {{ strtoupper($statusVal) }}
                </span>
            @endif
        </div>
    </div>

    <!-- Overview Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Card 1: Payment Metadata -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center space-x-2">
                <i class="fa-solid fa-credit-card text-slate-500"></i>
                <span>Payment Information</span>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Amount Charged</span>
                    <span class="font-bold text-slate-900 text-sm">${{ number_format($payment->amount, 2) }} {{ $payment->currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Payment Method</span>
                    <span class="font-semibold text-slate-800 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Transaction Reference</span>
                    <span class="font-mono text-slate-700 select-all">{{ $payment->transaction_id ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Created At</span>
                    <span class="text-slate-800">{{ $payment->created_at->format('M d, Y H:i:s') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Paid Timestamp</span>
                    <span class="text-slate-800">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i:s') : 'Pending Capture' }}</span>
                </div>
                @if($payment->failure_reason)
                <div class="p-2.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-[11px]">
                    <strong>Failure Reason:</strong> {{ $payment->failure_reason }}
                </div>
                @endif
            </div>
        </div>

        <!-- Card 2: Associated Order -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-bag-shopping text-slate-500"></i>
                    <span>Associated Order</span>
                </div>
                @if($payment->order)
                <a href="{{ route('admin.orders.show', $payment->order->id) }}" class="text-indigo-600 hover:underline font-semibold text-[11px]">
                    View Order &rarr;
                </a>
                @endif
            </div>
            @if($payment->order)
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-500">Order Number</span>
                    <span class="font-mono font-bold text-slate-900">{{ $payment->order->order_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Order Status</span>
                    <span class="font-semibold text-slate-800 uppercase text-[10px]">{{ $payment->order->status->value }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Customer Name</span>
                    <span class="font-semibold text-slate-800">{{ $payment->order->customer->name ?? 'Guest' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Customer Email</span>
                    <span class="text-slate-700">{{ $payment->order->customer->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Order Total</span>
                    <span class="font-bold text-slate-900">${{ number_format($payment->order->total, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Items Count</span>
                    <span class="text-slate-800">{{ $payment->order->items->count() }} line item(s)</span>
                </div>
            </div>
            @else
            <p class="text-slate-400 italic">No associated order found.</p>
            @endif
        </div>

        <!-- Card 3: Gateway Payload Inspection -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center space-x-2">
                <i class="fa-solid fa-code text-slate-500"></i>
                <span>Raw Gateway Response</span>
            </div>
            <div class="bg-slate-900 text-slate-200 p-3 rounded-lg overflow-x-auto font-mono text-[10px] max-h-48 leading-relaxed">
                <pre>{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No gateway payload recorded' }}</pre>
            </div>
        </div>

    </div>

    <!-- Transaction Audit Trail -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 font-bold text-slate-900 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-slate-500"></i>
                <span>Transactions Audit Trail ({{ $payment->transactions->count() }})</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Reference ID</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payment->transactions as $txn)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-mono text-slate-600">#{{ $txn->id }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-800 uppercase text-[10px]">{{ $txn->type }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-600">{{ $txn->reference_id ?? 'None' }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">${{ number_format($txn->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $txn->status === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                                {{ strtoupper($txn->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $txn->created_at->format('M d, Y H:i:s') }}</td>
                        <td class="px-4 py-3">
                            <details class="cursor-pointer">
                                <summary class="text-indigo-600 hover:underline text-[10px]">View Payload</summary>
                                <div class="mt-2 p-2 bg-slate-900 text-slate-200 font-mono text-[9px] rounded max-w-md overflow-x-auto">
                                    <pre>{{ json_encode($txn->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </details>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-400">No transaction logs recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
