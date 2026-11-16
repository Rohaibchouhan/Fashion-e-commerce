@extends('admin.layouts.app')

@section('title', 'Add New Garment')
@section('page_title', 'Create New Garment')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="productCreate()">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 text-xs">
        @csrf

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

        <!-- Card 1: Basic Information -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-tag text-slate-400"></i>
                <span>Basic Product Information</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Product Name *</label>
                    <input type="text" name="name" x-model="name" @input="updateSlug()" value="{{ old('name') }}" required placeholder="e.g. Heavyweight Relaxed Tee" class="w-full px-3 py-2 rounded-lg border @error('name') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1">
                    @error('name')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-semibold text-slate-700">URL Slug *</label>
                        <button type="button" @click="slugLocked = !slugLocked" class="text-[10px] text-blue-600 hover:underline">
                            <span x-text="slugLocked ? 'Edit manually' : 'Lock to name'"></span>
                        </button>
                    </div>
                    <input type="text" name="slug" x-model="slug" :readonly="slugLocked" value="{{ old('slug') }}" required placeholder="heavyweight-relaxed-tee" class="w-full px-3 py-2 rounded-lg border @error('slug') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-mono text-slate-700 focus:outline-none focus:ring-1" :class="slugLocked ? 'bg-slate-50' : 'bg-white'">
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
                    <input type="text" name="sku" x-model="masterSku" value="{{ old('sku') }}" required placeholder="e.g. HRT-100" class="w-full px-3 py-2 rounded-lg border @error('sku') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-mono focus:outline-none focus:ring-1">
                    @error('sku')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Subtitle / Material Highlight</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle') }}" placeholder="e.g. 240 GSM organic ring-spun cotton" class="w-full px-3 py-2 rounded-lg border @error('subtitle') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1">
                    @error('subtitle')
                        <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Department / Gender *</label>
                    <select name="gender" required class="w-full px-3 py-2 rounded-lg border @error('gender') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 @enderror text-slate-700 focus:outline-none">
                        <option value="men" {{ old('gender') === 'men' ? 'selected' : '' }}>Men's Collection</option>
                        <option value="women" {{ old('gender') === 'women' ? 'selected' : '' }}>Women's Collection</option>
                        <option value="unisex" {{ old('gender') === 'unisex' ? 'selected' : '' }}>Unisex / All</option>
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
                        <option value="">Select Category...</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
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
                        <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
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
                <textarea name="description" rows="4" required placeholder="Describe garment cut, hand-feel, silhouette, and everyday wearability..." class="w-full px-3 py-2 rounded-lg border @error('description') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror focus:outline-none focus:ring-1 leading-relaxed">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        <!-- Card 2: Pricing & Collection Flags -->
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
                        <input type="number" step="0.01" name="regular_price" x-model="regularPrice" value="{{ old('regular_price') }}" required placeholder="45.00" class="w-full pl-7 pr-3 py-2 rounded-lg border @error('regular_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold focus:outline-none focus:ring-1">
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
                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price') }}" placeholder="Optional promotional price" class="w-full pl-7 pr-3 py-2 rounded-lg border @error('sale_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold text-emerald-700 focus:outline-none focus:ring-1">
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
                        <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}" placeholder="Wholesale cost" class="w-full pl-7 pr-3 py-2 rounded-lg border @error('cost_price') border-rose-500 bg-rose-50/20 text-rose-900 focus:ring-rose-500 @else border-slate-300 focus:ring-slate-900 @enderror font-semibold text-slate-600 focus:outline-none focus:ring-1">
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
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-slate-900">
                    <span class="font-semibold text-slate-800">Active in Store</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Featured</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_new" value="1" {{ old('is_new', '1') ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">New Arrival</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Best Seller</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }} class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700">Trending</span>
                </label>
            </div>
        </div>

        <!-- Card 3: Product Image Upload & Gallery -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-images text-slate-400"></i>
                    <span>Product Imagery & Gallery</span>
                </div>
                <span class="text-[11px] text-slate-400 font-normal">PNG, JPG, WebP up to 5MB</span>
            </h3>

            <!-- Main Cover Image -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Primary Cover Image *</label>
                <div class="flex items-start space-x-4">
                    <div class="w-28 h-36 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center bg-slate-50 relative overflow-hidden flex-shrink-0">
                        <template x-if="mainImagePreview">
                            <img :src="mainImagePreview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!mainImagePreview">
                            <div class="text-center p-2 text-slate-400">
                                <i class="fa-solid fa-cloud-arrow-up text-lg mb-1 block"></i>
                                <span class="text-[10px]">No image</span>
                            </div>
                        </template>
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" name="main_image" accept="image/*" @change="previewMainImage($event)" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 file:cursor-pointer">
                        @error('main_image')
                            <p class="text-rose-600 mt-1 text-[11px] font-medium flex items-center gap-1">
                                <i class="fa-solid fa-circle-exclamation text-[10px]"></i> {{ $message }}
                            </p>
                        @enderror
                        <p class="text-[11px] text-slate-400">Upload high-resolution editorial cover shot for cards and search results.</p>
                        <div class="pt-1">
                            <span class="text-[11px] text-slate-400">Or remote image URL:</span>
                            <input type="url" name="main_image_url" placeholder="https://images.unsplash.com/..." class="mt-1 w-full px-3 py-1.5 rounded-lg border border-slate-300 font-mono text-[11px]">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Gallery Images -->
            <div class="pt-3 border-t border-slate-100">
                <label class="block font-semibold text-slate-700 mb-1">Additional Gallery Angles (Multiple)</label>
                <input type="file" name="gallery_images[]" multiple accept="image/*" @change="previewGalleryImages($event)" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 file:cursor-pointer">
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

                <!-- Gallery Preview Grid -->
                <div class="mt-3 flex flex-wrap gap-3">
                    <template x-for="(img, idx) in galleryPreviews" :key="idx">
                        <div class="w-20 h-24 rounded-lg border border-slate-200 relative overflow-hidden bg-slate-50 shadow-2xs">
                            <img :src="img" class="w-full h-full object-cover">
                            <button type="button" @click="removeGalleryPreview(idx)" class="absolute top-1 right-1 w-5 h-5 bg-rose-600 text-white rounded-full flex items-center justify-center text-[10px]">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Card 4: Variant Matrix & Inventory -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <i class="fa-solid fa-layer-group text-slate-400"></i>
                        <span>Color & Size Variants (SKU Matrix)</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Every combination automatically tracks live inventory in MySQL.</p>
                </div>
                <div class="flex items-center space-x-2 self-start sm:self-auto">
                    <button type="button" @click="openMatrixGenerator = !openMatrixGenerator" class="px-3 py-1.5 rounded-lg border border-slate-300 font-semibold hover:bg-slate-50 text-slate-700 flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-wand-magic-sparkles text-indigo-600"></i>
                        <span>Auto-Generate Matrix</span>
                    </button>
                    <button type="button" @click="addVariantRow()" class="px-3 py-1.5 bg-slate-900 text-white rounded-lg font-semibold hover:bg-slate-800 flex items-center space-x-1.5 transition">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Add Row</span>
                    </button>
                </div>
            </div>

            <!-- Quick Matrix Generator Box -->
            <div x-show="openMatrixGenerator" x-transition class="p-4 bg-indigo-50/70 border border-indigo-100 rounded-xl space-y-3">
                <div class="font-bold text-indigo-950 flex items-center justify-between">
                    <span>Bulk Matrix Generator</span>
                    <button type="button" @click="openMatrixGenerator = false" class="text-slate-400 hover:text-slate-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Colors (Comma separated):</label>
                        <input type="text" x-model="bulkColors" placeholder="e.g. Oatmeal Heather, Olive Sage, Washed Black" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Sizes (Comma separated):</label>
                        <input type="text" x-model="bulkSizes" placeholder="e.g. S, M, L, XL" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
                    </div>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <div class="text-[11px] text-slate-500">Default starting stock: <input type="number" x-model="bulkStock" class="w-16 px-2 py-1 rounded border border-slate-300 bg-white inline font-bold"></div>
                    <button type="button" @click="generateMatrix()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg">
                        Populate Variant Rows
                    </button>
                </div>
            </div>

            <!-- Variants Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-3 py-2">Color Name</th>
                            <th class="px-3 py-2">Hex Dot</th>
                            <th class="px-3 py-2">Size</th>
                            <th class="px-3 py-2">Variant SKU *</th>
                            <th class="px-3 py-2">Price ($)</th>
                            <th class="px-3 py-2">Starting Stock</th>
                            <th class="px-3 py-2 text-right">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(v, index) in variants" :key="index">
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-3 py-2">
                                    <input type="text" :name="`variants[${index}][color]`" x-model="v.color" placeholder="e.g. Olive" class="w-28 px-2 py-1.5 rounded border border-slate-300">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center space-x-1.5">
                                        <input type="color" :name="`variants[${index}][color_hex]`" x-model="v.color_hex" class="w-6 h-6 rounded border border-slate-300 p-0 cursor-pointer">
                                        <span class="font-mono text-[10px] text-slate-500" x-text="v.color_hex"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`variants[${index}][size]`" x-model="v.size" placeholder="M" class="w-16 px-2 py-1.5 rounded border border-slate-300 text-center font-bold">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" :name="`variants[${index}][sku]`" x-model="v.sku" required placeholder="SKU" class="w-32 px-2 py-1.5 rounded border border-slate-300 font-mono text-[11px]">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" :name="`variants[${index}][price]`" x-model="v.price" class="w-20 px-2 py-1.5 rounded border border-slate-300">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" :name="`variants[${index}][stock]`" x-model="v.stock" class="w-20 px-2 py-1.5 rounded border border-slate-300 font-bold text-slate-900">
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="removeVariantRow(index)" class="p-1 text-slate-400 hover:text-rose-600">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <template x-if="variants.length === 0">
                <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-lg">
                    No variants added. Click "Auto-Generate Matrix" or "Add Row" above to configure size and color combinations.
                </div>
            </template>
        </div>

        <!-- Card 5: Attributes & SEO -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center space-x-2">
                <i class="fa-solid fa-magnifying-glass-chart text-slate-400"></i>
                <span>Fabric Attributes & Search Engine Optimization (SEO)</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Fabric Composition</label>
                    <input type="text" name="fabric" value="{{ old('fabric') }}" placeholder="e.g. 100% GOTS Organic Cotton" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Fit & Drape</label>
                    <input type="text" name="fit" value="{{ old('fit') }}" placeholder="e.g. Relaxed Boxy Fit. True to size." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">SEO Meta Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title') }}" placeholder="Garment Name | WearDay Atelier" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">SEO Meta Description</label>
                    <input type="text" name="seo_description" value="{{ old('seo_description') }}" placeholder="Concise summary for search engine snippet..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
            <a href="{{ route('admin.products') }}" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition shadow-xs flex items-center space-x-2">
                <i class="fa-solid fa-check"></i>
                <span>Publish Garment to Catalog</span>
            </button>
        </div>
    </form>
</div>

<script>
function productCreate() {
    return {
        name: '',
        slug: '',
        slugLocked: true,
        masterSku: '',
        regularPrice: '',
        mainImagePreview: null,
        galleryPreviews: [],
        openMatrixGenerator: false,
        bulkColors: 'Black, Chalk White, Olive',
        bulkSizes: 'S, M, L, XL',
        bulkStock: 15,
        variants: [
            { color: 'Black', color_hex: '#111111', size: 'M', sku: 'RFT-BLK-M', price: '', stock: 20 },
            { color: 'Chalk White', color_hex: '#f5f5f5', size: 'M', sku: 'RFT-WHT-M', price: '', stock: 20 },
        ],
        updateSlug() {
            if (this.slugLocked) {
                this.slug = this.name.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
            }
        },
        previewMainImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => this.mainImagePreview = e.target.result;
                reader.readAsDataURL(file);
            }
        },
        previewGalleryImages(event) {
            const files = event.target.files;
            if (files) {
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (e) => this.galleryPreviews.push(e.target.result);
                    reader.readAsDataURL(file);
                });
            }
        },
        removeGalleryPreview(idx) {
            this.galleryPreviews.splice(idx, 1);
        },
        addVariantRow() {
            const skuPrefix = this.masterSku || 'WD';
            this.variants.push({
                color: 'Default',
                color_hex: '#000000',
                size: 'M',
                sku: `${skuPrefix}-${this.variants.length + 1}`,
                price: this.regularPrice || '',
                stock: 10
            });
        },
        removeVariantRow(idx) {
            this.variants.splice(idx, 1);
        },
        generateMatrix() {
            const colors = this.bulkColors.split(',').map(c => c.trim()).filter(Boolean);
            const sizes = this.bulkSizes.split(',').map(s => s.trim()).filter(Boolean);
            const skuBase = this.masterSku || 'WD';
            const price = this.regularPrice || '';
            const stock = parseInt(this.bulkStock) || 0;

            const colorHexMap = {
                'black': '#111111',
                'white': '#ffffff',
                'chalk white': '#f5f5f5',
                'olive': '#556b2f',
                'olive sage': '#5a6855',
                'navy': '#000080',
                'grey': '#808080',
                'oatmeal': '#e5ded4',
                'beige': '#f5f5dc',
                'denim': '#1560bd',
                'brown': '#8b4513'
            };

            this.variants = [];
            colors.forEach(col => {
                const hex = colorHexMap[col.toLowerCase()] || '#333333';
                sizes.forEach(sz => {
                    const colCode = col.substring(0, 3).toUpperCase();
                    this.variants.push({
                        color: col,
                        color_hex: hex,
                        size: sz,
                        sku: `${skuBase}-${colCode}-${sz}`,
                        price: price,
                        stock: stock
                    });
                });
            });
            this.openMatrixGenerator = false;
        }
    }
}
</script>
@endsection
