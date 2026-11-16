@extends('admin.layouts.app')

@section('title', 'Payments')
@section('page_title', 'Payment Transactions & Gateway Audits')

@section('content')
<div class="space-y-6 text-xs">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
            Payment Records
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Transaction Reference</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Paid Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $pay)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-900">
                            <a href="{{ route('admin.orders.show', $pay->order_id) }}" class="text-indigo-600 hover:underline">
                                {{ $pay->order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-800">{{ $pay->order->customer->name }}</td>
                        <td class="px-4 py-3 capitalize font-medium text-slate-700">{{ str_replace('_', ' ', $pay->payment_method) }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">{{ $pay->transaction_id ?? 'None' }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">${{ number_format($pay->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $pay->status->value === 'paid' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                                {{ strtoupper($pay->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $pay->paid_at ? $pay->paid_at->format('M d, Y H:i:s') : 'Pending' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No payment logs recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection

