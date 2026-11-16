@extends('admin.layouts.app')

@section('title', 'Garment Overview — ' . $product->name)
@section('page_title', 'Garment Details: ' . $product->name)

@section('content')
<div class="max-w-6xl mx-auto space-y-6 text-xs">
    <!-- Header Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-3">
            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                {{ $product->is_active ? 'Active in Store' : 'Inactive / Draft' }}
            </span>
            <span class="font-mono text-slate-500">{{ $product->sku }}</span>
            <span class="text-slate-300">|</span>
            <span class="text-slate-500 capitalize">{{ $product->gender }}'s Department</span>
        </div>

        <div class="flex items-center space-x-2">
            <a href="http://localhost:3000/products/{{ $product->slug }}" target="_blank" class="px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold flex items-center space-x-1.5 transition">
                <i class="fa-solid fa-arrow-up-right-from-square text-[11px] text-blue-600"></i>
                <span>Open in Storefront</span>
            </a>
            <a href="{{ route('admin.products.edit', $product->id) }}" class="px-4 py-2 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg flex items-center space-x-1.5 transition shadow-xs">
                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                <span>Edit Garment</span>
            </a>
            <a href="{{ route('admin.products') }}" class="px-3 py-2 border border-slate-300 text-slate-600 rounded-lg hover:bg-slate-50 transition">
                Back
            </a>
        </div>
    </div>

    <!-- Overview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Imagery & Details -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Cover & Gallery -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Product Imagery</h4>
                @php
                    $cover = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                @endphp
                <div class="h-80 w-full rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                    <img src="{{ $cover?->url ?? 'https://placehold.co/400x500?text=No+Photo' }}" class="w-full h-full object-cover">
                </div>

                @if($product->images->count() > 1)
                <div class="grid grid-cols-4 gap-2 pt-2">
                    @foreach($product->images as $img)
                    <div class="h-18 rounded-lg overflow-hidden border {{ $img->is_primary ? 'border-amber-400 ring-1 ring-amber-400' : 'border-slate-200' }} bg-slate-50">
                        <img src="{{ $img->url }}" class="w-full h-full object-cover">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Pricing & Margins -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Pricing & Commercials</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Retail Price</div>
                        <div class="text-base font-bold text-slate-900 mt-0.5">${{ number_format($product->regular_price, 2) }}</div>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Current Sale Price</div>
                        <div class="text-base font-bold {{ $product->sale_price ? 'text-emerald-700' : 'text-slate-400' }} mt-0.5">
                            {{ $product->sale_price ? '$' . number_format($product->sale_price, 2) : 'None' }}
                        </div>
                    </div>
                    @if($product->cost_price)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Cost of Goods</div>
                        <div class="text-base font-bold text-slate-700 mt-0.5">${{ number_format($product->cost_price, 2) }}</div>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                        <div class="text-[10px] text-emerald-800 uppercase font-semibold">Gross Margin</div>
                        @php
                            $effective = $product->sale_price ?? $product->regular_price;
                            $margin = $effective - $product->cost_price;
                            $marginPct = ($margin / $effective) * 100;
                        @endphp
                        <div class="text-base font-bold text-emerald-800 mt-0.5">${{ number_format($margin, 2) }} ({{ round($marginPct) }}%)</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Variants, Inventory & Description -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Title Card -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $product->brand->name ?? 'WearDay Studio' }} &bull; {{ $product->category->name ?? 'Uncategorized' }}</span>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">{{ $product->name }}</h2>
                        @if($product->subtitle)
                        <p class="text-slate-500 mt-0.5">{{ $product->subtitle }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-slate-400">Total Live Stock</div>
                        <div class="text-xl font-black {{ $product->total_stock <= 5 ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $product->total_stock }} units
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">SKU: {{ $product->sku }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">Slug: {{ $product->slug }}</span>
                    @if($product->is_featured) <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span> @endif
                    @if($product->is_new) <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">New In</span> @endif
                    @if($product->is_bestseller) <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Best Seller</span> @endif
                </div>
            </div>

            <!-- Variants & Warehouse Inventory -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                    <span>Active Size & Color Variants ({{ $product->variants->count() }})</span>
                    <span class="text-[11px] text-slate-400 font-normal">Tracked via <code>inventories</code> relational table</span>
                </h4>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-3 py-2.5">Color</th>
                                <th class="px-3 py-2.5">Size</th>
                                <th class="px-3 py-2.5">Variant SKU</th>
                                <th class="px-3 py-2.5">Unit Price</th>
                                <th class="px-3 py-2.5">Physical Stock</th>
                                <th class="px-3 py-2.5">Reserved</th>
                                <th class="px-3 py-2.5">Available to Sell</th>
                                <th class="px-3 py-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($product->variants as $v)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="w-3.5 h-3.5 rounded-full border border-slate-300" style="background-color: {{ $v->color_hex ?? '#000' }}"></span>
                                        <span class="font-semibold text-slate-800">{{ $v->color ?: 'Standard' }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 font-bold text-slate-800">{{ $v->size ?: 'OS' }}</td>
                                <td class="px-3 py-2.5 font-mono text-[11px] text-slate-600">{{ $v->sku }}</td>
                                <td class="px-3 py-2.5 font-semibold text-slate-900">${{ number_format($v->price, 2) }}</td>
                                <td class="px-3 py-2.5 font-bold text-slate-900">{{ $v->stock }}</td>
                                <td class="px-3 py-2.5 font-mono text-slate-400">{{ $v->inventory?->reserved_quantity ?? 0 }}</td>
                                <td class="px-3 py-2.5 font-bold {{ $v->available_stock <= 5 ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ $v->available_stock }} units
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $v->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $v->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-slate-400">No variants configured for this garment.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Description & Attributes -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <h4 class="font-bold text-slate-900 border-b border-slate-100 pb-3">Garment Specifications & Copy</h4>
                <div class="prose prose-xs max-w-none text-slate-700 leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Fabric Composition</span>
                        <div class="text-slate-800 font-medium">{{ $product->fabric ?: 'Standard European Textile blend' }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Fit & Drape Profile</span>
                        <div class="text-slate-800 font-medium">{{ $product->fit ?: 'Regular True-to-size' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

