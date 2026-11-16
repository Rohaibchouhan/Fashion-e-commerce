@extends('admin.layouts.app')

@section('title', 'Inventory Movements')
@section('page_title', 'Inventory Audit & Movement Ledger')

@section('content')
<div class="space-y-5 text-xs">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.inventory') }}" class="text-slate-600 hover:text-slate-900 font-medium">&larr; Back to Current Stock</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Garment & Variant</th>
                        <th class="px-4 py-3">Movement Type</th>
                        <th class="px-4 py-3">Change</th>
                        <th class="px-4 py-3">Before &rarr; After</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Actor / Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $m)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $m->created_at->format('M d, Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <div>{{ $m->inventory->product->name ?? 'N/A' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $m->variant->sku ?? 'All Variants' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-800">
                                {{ strtoupper(str_replace('_', ' ', $m->type)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-bold {{ $m->quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $m->quantity_change > 0 ? "+{$m->quantity_change}" : $m->quantity_change }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 font-mono">
                            {{ $m->quantity_before }} &rarr; <span class="font-bold text-slate-900">{{ $m->quantity_after }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-[11px]">
                            {{ class_basename($m->reference_type) }} #{{ $m->reference_id ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <div>{{ $m->notes ?? '—' }}</div>
                            <div class="text-[10px] text-slate-400">{{ $m->user->name ?? 'System' }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No inventory movement events recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection

