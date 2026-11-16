@extends('admin.layouts.app')

@section('title', 'Refunds')
@section('page_title', 'Customer Refunds & Payouts')

@section('content')
<div class="space-y-5 text-xs">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Refund #</th>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Processed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($refunds as $ref)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-bold text-slate-900 font-mono">{{ $ref->refund_number }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">
                            <a href="{{ route('admin.orders.show', $ref->order_id) }}" class="text-indigo-600 hover:underline">
                                {{ $ref->order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-800">{{ $ref->order->customer->name }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">${{ number_format($ref->amount, 2) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $ref->reason }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase">
                                {{ $ref->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $ref->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No refunds recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $refunds->links() }}
        </div>
    </div>
</div>
@endsection

