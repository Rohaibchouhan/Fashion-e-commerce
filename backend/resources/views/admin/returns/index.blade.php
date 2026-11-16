@extends('admin.layouts.app')

@section('title', 'Returns & RMAs')
@section('page_title', 'Customer Returns & Exchanges')

@section('content')
<div class="space-y-5 text-xs">
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200">
        <form method="GET" class="flex items-center gap-3">
            <select name="status" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                <option value="all">All Return Statuses</option>
                @foreach($statuses as $st)
                <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">RMA Number</th>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Resolution</th>
                        <th class="px-4 py-3">Refund Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returns as $ret)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-bold text-slate-900 font-mono">{{ $ret->rma_number }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $ret->order->order_number }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ $ret->user->name }}</td>
                        <td class="px-4 py-3 font-semibold uppercase text-[10px] {{ $ret->resolution === 'refund' ? 'text-blue-700' : 'text-purple-700' }}">
                            {{ $ret->resolution }}
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-900">${{ number_format($ret->refund_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-800">
                                {{ $ret->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $ret->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.returns.show', $ret->id) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded font-medium text-xs transition">
                                Review RMA
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">No return requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $returns->links() }}
        </div>
    </div>
</div>
@endsection

