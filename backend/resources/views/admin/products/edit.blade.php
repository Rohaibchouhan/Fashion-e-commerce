@extends('admin.layouts.app')

@section('title', 'Edit Garment — ' . $product->name)
@section('page_title', 'Edit Garment: ' . $product->name)

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="productEdit()">
    <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs">
        @csrf
        @method('PUT')

        @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 space-y-1">
            <div class="font-bold flex items-center space-x-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside text-[11px] pl-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Header Actions Bar -->
        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center space-x-3">
                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                    {{ $product->is_active ? 'Active in Store' : 'Inactive / Draft' }}
                </span>
                <span class="text-slate-400">|</span>
                <span class="font-mono text-slate-600 font-semibold">{{ $product->sku }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="http://localhost:3000/products/{{ $product->slug }}" target="_blank" class="px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold flex items-center space-x-1.5 transition">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[11px] text-blue-600"></i>
                    <span>Preview in Storefront</span>
                </a>
                <a href="{{ route('admin.products.show', $product->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold rounded-lg transition">
                    Overview
                </a>
            </div>
        </div>

        <!-- Card 1: Basic Information -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-tag text-slate-400"></i>
                <span>Basic Product Information</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-3 py-2 rounded-lg border @error('name') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1">
                    @error('name')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">URL Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required class="w-full px-3 py-2 rounded-lg border @error('slug') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-mono text-slate-700 focus:outline-none focus:ring-1">
                    @error('slug')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Master SKU *</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required class="w-full px-3 py-2 rounded-lg border @error('sku') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-mono focus:outline-none focus:ring-1">
                    @error('sku')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Subtitle / Badge</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" class="w-full px-3 py-2 rounded-lg border @error('subtitle') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1">
                    @error('subtitle')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Department / Gender *</label>
                    <select name="gender" required class="w-full px-3 py-2 rounded-lg border @error('gender') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 @enderror text-slate-700 focus:outline-none">
                        <option value="men" {{ old('gender', $product->gender) === 'men' ? 'selected' : '' }}>Men's Collection</option>
                        <option value="women" {{ old('gender', $product->gender) === 'women' ? 'selected' : '' }}>Women's Collection</option>
                        <option value="unisex" {{ old('gender', $product->gender) === 'unisex' ? 'selected' : '' }}>Unisex / All</option>
                    </select>
                    @error('gender')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Category *</label>
                    <select name="category_id" required class="w-full px-3 py-2 rounded-lg border @error('category_id') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 @enderror text-slate-700 focus:outline-none">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} ({{ ucfirst($cat->gender) }})
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Brand</label>
                    <select name="brand_id" class="w-full px-3 py-2 rounded-lg border @error('brand_id') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 @enderror text-slate-700 focus:outline-none">
                        <option value="">WearDay Studio (In-House Default)</option>
                        @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Full Description *</label>
                <textarea name="description" rows="4" required class="w-full px-3 py-2 rounded-lg border @error('description') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1 leading-relaxed">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <!-- Card 2: Pricing & Merchandising -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-dollar-sign text-slate-400"></i>
                <span>Pricing & Merchandising</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Regular Retail Price ($) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold">$</span>
                        <input type="number" step="0.01" name="regular_price" value="{{ old('regular_price', $product->regular_price) }}" required class="w-full pl-7 pr-3 py-2 rounded-lg border @error('regular_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold focus:outline-none focus:ring-1">
                    </div>
                    @error('regular_price')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Sale Price ($)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold">$</span>
                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" placeholder="Leave blank if not on sale" class="w-full pl-7 pr-3 py-2 rounded-lg border @error('sale_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold text-emerald-700 focus:outline-none focus:ring-1">
                    </div>
                    @error('sale_price')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Cost Price ($) [Admin Margins]</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-400 font-bold">$</span>
                        <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" placeholder="Wholesale cost" class="w-full pl-7 pr-3 py-2 rounded-lg border @error('cost_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold text-slate-600 focus:outline-none focus:ring-1">
                    </div>
                    @error('cost_price')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Merchandising Badges -->
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 grid grid-cols-2 sm:grid-cols-5 gap-3">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="font-semibold text-slate-800">Active in Store</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Featured</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">New Arrival</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Best Seller</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_trending" value="1" {{ old('is_trending', $product->is_trending) ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Trending</span>
                </label>
            </div>
        </div>

        <!-- Card 3: Image Gallery & Management -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-5">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-images text-slate-400"></i>
                    <span>Product Imagery & Gallery ({{ $product->images->count() }} Images)</span>
                </div>
                <span class="text-[11px] text-slate-400 font-normal">Stored in Laravel Public Storage</span>
            </h3>

            <!-- Existing Images Gallery Grid -->
            <div>
                <label class="block font-semibold text-slate-700 mb-2">Existing Product Images</label>
                @if($product->images->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    @foreach($product->images as $img)
                    <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 relative group shadow-2xs">
                        <div class="h-32 w-full relative">
                            <img src="{{ $img->url }}" class="w-full h-full object-cover">
                            @if($img->is_primary)
                            <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-400 text-slate-900 shadow-xs flex items-center space-x-1">
                                <i class="fa-solid fa-star text-[8px]"></i>
                                <span>Cover</span>
                            </span>
                            @endif
                        </div>
                        <div class="p-2 bg-white flex items-center justify-between text-[11px] border-t border-slate-100">
                            @if(!$img->is_primary)
                            <form action="{{ route('admin.products.images.primary', [$product->id, $img->id]) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-[10px] font-bold" title="Set as primary cover">
                                    Set Cover
                                </button>
                            </form>
                            @else
                            <span class="text-[10px] font-semibold text-slate-400">Primary</span>
                            @endif

                            <form action="{{ route('admin.products.images.delete', [$product->id, $img->id]) }}" method="POST" onsubmit="return confirm('Remove this image from gallery?')">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-rose-600 p-1" title="Delete image">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-4 bg-amber-50 text-amber-800 rounded-lg text-xs">
                    No images currently attached. Upload a cover and gallery images below.
                </div>
                @endif
            </div>

            <!-- Upload New Images -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <!-- Replace / Set New Cover -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Replace Cover Image</label>
                    <input type="file" name="main_image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 file:cursor-pointer">
                    @error('main_image')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                    <p class="text-[11px] text-slate-400 mt-1">Uploading a new cover will become the primary product photo.</p>
                </div>

                <!-- Add More Gallery Shots -->
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Upload Additional Gallery Images</label>
                    <input type="file" name="gallery_images[]" multiple accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 file:cursor-pointer">
                    @error('gallery_images')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                    @error('gallery_images.*')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                    <p class="text-[11px] text-slate-400 mt-1">Select multiple images to append to the customer-facing gallery.</p>
                </div>
            </div>
        </div>

        <!-- Card 4: Variant Management & Live Inventory -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <i class="fa-solid fa-layer-group text-slate-400"></i>
                        <span>Garment Variants & Inventory Synchronization</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Edit variant prices, modify inventory levels, or add new size/color combinations.</p>
                </div>
                <button type="button" @click="addNewVariantRow()" class="px-3 py-1.5 bg-slate-900 text-white rounded-lg font-semibold hover:bg-slate-800 flex items-center space-x-1.5 transition">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span>Add New Variant</span>
                </button>
            </div>

            <!-- Existing Variants Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-3 py-2">Color / Swatch</th>
                            <th class="px-3 py-2">Size</th>
                            <th class="px-3 py-2">SKU *</th>
                            <th class="px-3 py-2">Retail Price ($)</th>
                            <th class="px-3 py-2">Physical Stock</th>
                            <th class="px-3 py-2">Reserved</th>
                            <th class="px-3 py-2">Available</th>
                            <th class="px-3 py-2">Active</th>
                            <th class="px-3 py-2 text-right">Delete</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($product->variants as $v)
                        <tr class="hover:bg-slate-50/50">
                            <!-- Color -->
                            <td class="px-3 py-2">
                                <div class="flex items-center space-x-2">
                                    <span class="w-4 h-4 rounded-full border border-slate-300 flex-shrink-0" style="background-color: {{ $v->color_hex ?? '#000' }}"></span>
                                    <span class="font-semibold text-slate-800">{{ $v->color ?: 'Standard' }}</span>
                                </div>
                            </td>

                            <!-- Size -->
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-bold text-slate-800">{{ $v->size ?: 'One Size' }}</span>
                            </td>

                            <!-- SKU -->
                            <td class="px-3 py-2 font-mono">
                                <input type="text" name="existing_variants[{{ $v->id }}][sku]" value="{{ old("existing_variants.{$v->id}.sku", $v->sku) }}" required class="w-32 px-2 py-1 rounded border border-slate-300 font-mono text-[11px]">
                            </td>

                            <!-- Price -->
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" name="existing_variants[{{ $v->id }}][price]" value="{{ old("existing_variants.{$v->id}.price", $v->price) }}" required class="w-20 px-2 py-1 rounded border border-slate-300">
                            </td>

                            <!-- Stock (Physical) -->
                            <td class="px-3 py-2">
                                <input type="number" min="0" name="existing_variants[{{ $v->id }}][stock]" value="{{ old("existing_variants.{$v->id}.stock", $v->stock) }}" required class="w-20 px-2 py-1 rounded border border-slate-300 font-bold text-slate-900">
                            </td>

                            <!-- Reserved -->
                            <td class="px-3 py-2 text-slate-400 font-mono text-center">
                                {{ $v->inventory?->reserved_quantity ?? 0 }}
                            </td>

                            <!-- Available -->
                            <td class="px-3 py-2 font-bold {{ $v->available_stock <= 5 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $v->available_stock }}
                            </td>

                            <!-- Active Switch -->
                            <td class="px-3 py-2">
                                <input type="checkbox" name="existing_variants[{{ $v->id }}][is_active]" value="1" {{ $v->is_active ? 'checked' : '' }} class="rounded border-slate-300">
                            </td>

                            <!-- Delete Variant -->
                            <td class="px-3 py-2 text-right">
                                <label class="text-rose-500 hover:text-rose-700 cursor-pointer text-[11px] flex items-center justify-end space-x-1" title="Mark for removal">
                                    <input type="checkbox" name="deleted_variant_ids[]" value="{{ $v->id }}" class="rounded text-rose-600 border-slate-300">
                                    <span class="text-[10px]">Remove</span>
                                </label>
                            </td>
                        </tr>
                        @endforeach

                        <!-- Dynamic New Variant Rows -->
                        <template x-for="(nv, nIdx) in newVariants" :key="nIdx">
                            <tr class="bg-indigo-50/40">
                                <td class="px-3 py-2">
                                    <input type="text" :name="`new_variants[${nIdx}][color]`" x-model="nv.color" placeholder="Color Name" required class="w-24 px-2 py-1 rounded border border-indigo-200">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`new_variants[${nIdx}][size]`" x-model="nv.size" placeholder="Size" required class="w-16 px-2 py-1 rounded border border-indigo-200 font-bold text-center">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`new_variants[${nIdx}][sku]`" x-model="nv.sku" placeholder="SKU" required class="w-32 px-2 py-1 rounded border border-indigo-200 font-mono text-[11px]">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" :name="`new_variants[${nIdx}][price]`" x-model="nv.price" class="w-20 px-2 py-1 rounded border border-indigo-200">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" :name="`new_variants[${nIdx}][stock]`" x-model="nv.stock" class="w-20 px-2 py-1 rounded border border-indigo-200 font-bold">
                                </td>
                                <td class="px-3 py-2 text-center text-slate-400">-</td>
                                <td class="px-3 py-2 font-bold text-emerald-700" x-text="nv.stock || 0"></td>
                                <td class="px-3 py-2 text-emerald-700 font-semibold text-[10px]">New</td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="removeNewVariantRow(nIdx)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card 5: Attributes & SEO -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-magnifying-glass-chart text-slate-400"></i>
                <span>Fabric Attributes & SEO</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Fabric Composition</label>
                    <input type="text" name="fabric" value="{{ old('fabric', $product->fabric) }}" placeholder="e.g. 100% GOTS Organic Cotton" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Fit & Drape</label>
                    <input type="text" name="fit" value="{{ old('fit', $product->fit) }}" placeholder="e.g. Relaxed Boxy Fit. True to size." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">SEO Meta Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">SEO Meta Description</label>
                    <input type="text" name="seo_description" value="{{ old('seo_description', $product->seo_description) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
            <a href="{{ route('admin.products') }}" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition">
                Back to Catalog
            </a>
            <button type="submit" class="px-6 py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition shadow-xs flex items-center space-x-2">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save All Changes & Update Stock</span>
            </button>
        </div>
    </form>
</div>

<script>
function productEdit() {
    return {
        newVariants: [],
        addNewVariantRow() {
            this.newVariants.push({
                color: 'Black',
                color_hex: '#111111',
                size: 'XL',
                sku: '{{ $product->sku }}-XL',
                price: '{{ $product->effective_price }}',
                stock: 10
            });
        },
        removeNewVariantRow(idx) {
            this.newVariants.splice(idx, 1);
        }
    }
}
</script>
@endsection
