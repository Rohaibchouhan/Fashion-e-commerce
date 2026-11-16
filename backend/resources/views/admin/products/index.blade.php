@extends('admin.layouts.app')

@section('title', 'Product Catalog')
@section('page_title', 'Garments & Catalog Management')

@section('content')
<div class="space-y-5" x-data="{ deleteModalOpen: false, deleteAction: '', deleteProductName: '' }">
    <!-- Filters & Action Header -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Garment Inventory & Catalog</h3>
                <p class="text-xs text-slate-500">Manage apparel styles, size & color variants, pricing, and live warehouse inventory.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="px-4 py-2.5 bg-slate-950 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg flex items-center space-x-2 transition shadow-xs self-start md:self-auto">
                <i class="fa-solid fa-plus text-[11px]"></i>
                <span>Add New Garment</span>
            </a>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.products') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3 text-xs pt-3 border-t border-slate-100">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Search</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, SKU, slug..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
            </div>

            <!-- Category -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Category</label>
                <select name="category" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                    <option value="all">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @foreach($cat->children as $child)
                            <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <!-- Gender -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Department</label>
                <select name="gender" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                    <option value="all">All Departments</option>
                    <option value="men" {{ request('gender') === 'men' ? 'selected' : '' }}>Men</option>
                    <option value="women" {{ request('gender') === 'women' ? 'selected' : '' }}>Women</option>
                    <option value="unisex" {{ request('gender') === 'unisex' ? 'selected' : '' }}>Unisex</option>
                </select>
            </div>

            <!-- Brand -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Brand</label>
                <select name="brand" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                    <option value="all">All Brands</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ request('brand') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status & Stock -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status / Stock</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none">
                    <option value="all">Any Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>

            <!-- Sort & Actions -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sort By</label>
                    <select name="sort" class="w-full px-2 py-2 rounded-lg border border-slate-300 text-slate-700 focus:outline-none text-[11px]">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Stock: Low</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock: High</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                    </select>
                </div>
                <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-lg font-semibold hover:bg-slate-800 transition">
                    Apply
                </button>
                @if(request()->hasAny(['search', 'category', 'gender', 'brand', 'status', 'sort']))
                <a href="{{ route('admin.products') }}" class="p-2 text-slate-400 hover:text-slate-700 text-center" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Garment / Style</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Department & Category</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Available Stock</th>
                        <th class="px-4 py-3">Variants</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                    <tr class="hover:bg-slate-50/60 transition">
                        <!-- Garment Info -->
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                                @php
                                    $coverImg = $p->images->firstWhere('is_primary', true)?->url ?? $p->images->first()?->url ?? 'https://placehold.co/80x100?text=No+Img';
                                @endphp
                                <img src="{{ $coverImg }}" alt="{{ $p->name }}" class="w-11 h-13 object-cover rounded-md bg-slate-100 flex-shrink-0 border border-slate-200">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.show', $p->id) }}" class="font-bold text-slate-900 hover:text-blue-600 transition block truncate max-w-xs">
                                        {{ $p->name }}
                                    </a>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ $p->subtitle ?: $p->slug }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        @if($p->is_featured)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                                        @endif
                                        @if($p->is_new)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">New In</span>
                                        @endif
                                        @if($p->is_bestseller)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Best Seller</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- SKU -->
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-600 font-semibold">{{ $p->sku }}</td>

                        <!-- Category & Gender -->
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $p->category->name ?? 'Unassigned' }}</div>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                {{ $p->gender === 'men' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($p->gender === 'women' ? 'bg-pink-50 text-pink-700 border border-pink-200' : 'bg-slate-100 text-slate-700') }}">
                                {{ $p->gender }}
                            </span>
                        </td>

                        <!-- Price -->
                        <td class="px-4 py-3">
                            @if($p->sale_price)
                                <div class="font-bold text-emerald-700">${{ number_format($p->sale_price, 2) }}</div>
                                <div class="text-slate-400 line-through text-[10px]">${{ number_format($p->regular_price, 2) }}</div>
                            @else
                                <div class="font-bold text-slate-900">${{ number_format($p->regular_price, 2) }}</div>
                            @endif
                        </td>

                        <!-- Available Stock -->
                        <td class="px-4 py-3">
                            @php $totStock = $p->total_stock; @endphp
                            @if($totStock > 5)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $totStock }} units
                                </span>
                            @elseif($totStock > 0)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Low: {{ $totStock }} left
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Out of stock
                                </span>
                            @endif
                        </td>

                        <!-- Variant Count -->
                        <td class="px-4 py-3 text-slate-600 font-medium">
                            <span class="text-slate-800 font-semibold">{{ $p->variants->count() }}</span> options
                        </td>

                        <!-- Status Toggle -->
                        <td class="px-4 py-3">
                            <form action="{{ route('admin.products.toggle', $p->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 rounded-md text-[10px] font-bold transition flex items-center space-x-1.5
                                    {{ $p->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-300 hover:bg-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $p->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $p->is_active ? 'Active' : 'Inactive' }}</span>
                                </button>
                            </form>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <!-- View Details -->
                            <a href="{{ route('admin.products.show', $p->id) }}" class="p-1.5 text-slate-500 hover:text-slate-900 rounded hover:bg-slate-100 inline-block" title="View Garment Details">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            <!-- Edit -->
                            <a href="{{ route('admin.products.edit', $p->id) }}" class="p-1.5 text-blue-600 hover:text-blue-900 rounded hover:bg-blue-50 inline-block" title="Edit Garment">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </a>
                            <!-- Delete / Archive Modal trigger -->
                            <button type="button"
                                @click="deleteModalOpen = true; deleteAction = '{{ route('admin.products.destroy', $p->id) }}'; deleteProductName = '{{ addslashes($p->name) }}'"
                                class="p-1.5 text-rose-500 hover:text-rose-700 rounded hover:bg-rose-50 inline-block" title="Archive / Delete Garment">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-shirt text-3xl mb-2 text-slate-300 block"></i>
                            <span>No garments match current filter parameters.</span>
                            <div class="mt-2">
                                <a href="{{ route('admin.products') }}" class="text-blue-600 font-semibold hover:underline">Clear all filters</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <div>
                Showing <b>{{ $products->firstItem() ?? 0 }}</b> to <b>{{ $products->lastItem() ?? 0 }}</b> of <b>{{ $products->total() }}</b> garments
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>

    <!-- Safe Delete Confirmation Modal -->
    <div x-cloak x-show="deleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModalOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="deleteModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="deleteModalOpen" x-transition.scale class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100 p-6 space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900" id="modal-title">Archive Garment from Catalog?</h4>
                        <p class="text-xs text-slate-500 mt-1">
                            You are about to remove <span class="font-bold text-slate-900" x-text="deleteProductName"></span> from the active catalog.
                        </p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 space-y-2">
                    <div class="font-semibold text-slate-800 flex items-center space-x-1.5">
                        <i class="fa-solid fa-shield-halved text-blue-600"></i>
                        <span>Order Integrity Protection</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        If this garment is linked to historical customer orders, our database safety rules will preserve order item records intact while completely deactivating the product and hiding it from the Next.js storefront.
                    </p>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </button>
                    <form :action="deleteAction" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition shadow-xs">
                            Confirm Deletion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
