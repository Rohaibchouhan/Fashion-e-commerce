@extends('admin.layouts.app')

@section('title', 'Inventory & Stock')
@section('page_title', 'Warehouse Inventory Management')

@section('content')
<div class="space-y-5" x-data="{ adjustModal: false, selectedVariantId: null, currentQty: 0, variantTitle: '' }">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200">
        <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto text-xs">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product or SKU..." class="px-3 py-2 rounded-lg border border-slate-300 w-52 focus:outline-none">
            <label class="flex items-center space-x-1.5 text-slate-700 font-medium">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-slate-300">
                <span>Low Stock Only</span>
            </label>
            <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-lg font-medium">Search</button>
            @if(request()->hasAny(['search', 'low_stock']))
            <a href="{{ route('admin.inventory') }}" class="text-xs text-slate-500 hover:underline">Reset</a>
            @endif
        </form>

        <a href="{{ route('admin.inventory.movements') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold flex items-center space-x-1.5 transition">
            <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
            <span>Inventory Movements Log</span>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Garment / Variation</th>
                        <th class="px-4 py-3">Variant SKU</th>
                        <th class="px-4 py-3">Size / Color</th>
                        <th class="px-4 py-3">Physical Stock</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Available To Sell</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inventories as $inv)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $inv->product->images->first()?->url ?? '' }}" class="w-9 h-9 object-cover rounded bg-slate-100 flex-shrink-0">
                                <div class="font-bold text-slate-900">{{ $inv->product->name }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-600">{{ $inv->variant?->sku ?? $inv->product->sku }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            {{ $inv->variant?->size ?? 'N/A' }} / {{ $inv->variant?->color ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-900">{{ $inv->quantity }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $inv->reserved_quantity }}</td>
                        <td class="px-4 py-3 font-bold text-emerald-700">{{ $inv->available_quantity }}</td>
                        <td class="px-4 py-3">
                            @if($inv->is_out_of_stock)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Out of Stock</span>
                            @elseif($inv->is_low_stock)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Low Stock (≤{{ $inv->low_stock_threshold }})</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">In Stock</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($inv->variant)
                            <button @click="adjustModal = true; selectedVariantId = {{ $inv->variant_id }}; currentQty = {{ $inv->quantity }}; variantTitle = '{{ addslashes($inv->product->name . ' - ' . $inv->variant->color . ' / ' . $inv->variant->size) }}'" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded font-medium text-xs transition">
                                Adjust Stock
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">No inventory rows found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $inventories->links() }}
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div x-show="adjustModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-xs" @click.away="adjustModal = false">
            <h3 class="font-bold text-slate-900 text-sm mb-1">Stock Adjustment</h3>
            <p class="text-slate-500 mb-4" x-text="variantTitle"></p>

            <form :action="'/admin/inventory/' + selectedVariantId + '/adjust'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">New Physical Stock Count *</label>
                    <input type="number" name="new_quantity" x-model="currentQty" required min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reason for Adjustment *</label>
                    <select name="reason" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                        <option value="Physical Cycle Count Reconciled">Physical Cycle Count Reconciled</option>
                        <option value="New Warehouse Restock Shipment Received">New Warehouse Restock Shipment Received</option>
                        <option value="Damaged / Quality Writeoff">Damaged / Quality Writeoff</option>
                        <option value="Showroom Display Allocation">Showroom Display Allocation</option>
                    </select>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="adjustModal = false" class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

