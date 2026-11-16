@extends('admin.layouts.app')

@section('title', 'Banners & Badges')
@section('page_title', 'Homepage Banners & Trust Badges')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <!-- Banners -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                    Active Banners & Strips
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($banners as $b)
                    <div class="border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                        <img src="{{ $b->image_desktop }}" class="w-full h-32 object-cover">
                        <div class="p-3">
                            <div class="font-bold text-slate-900 text-sm">{{ $b->title }}</div>
                            <div class="text-slate-500 mt-1 line-clamp-2">{{ $b->subtitle }}</div>
                            <div class="mt-2 text-[10px] text-slate-400 font-mono">Pos: {{ $b->position }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Trust Badges -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                    Storefront Trust Badges
                </div>
                <div class="p-4 grid grid-cols-2 gap-4">
                    @foreach($trustBadges as $badge)
                    <div class="p-3 border border-slate-200 rounded-lg bg-slate-50">
                        <div class="font-bold text-slate-900">{{ $badge->title }}</div>
                        <div class="text-slate-500 mt-1">{{ $badge->description }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Add Banner Form -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-slate-900 text-sm mb-4">Add Homepage Banner</h4>
            <form action="{{ route('admin.cms.banners.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Banner Title *</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Subtitle / Caption</label>
                    <input type="text" name="subtitle" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Image Desktop URL *</label>
                    <input type="url" name="image_desktop" placeholder="https://images.unsplash.com/..." required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Button Text</label>
                        <input type="text" name="button_text" placeholder="Explore" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Button URL</label>
                        <input type="text" name="button_url" placeholder="/catalog" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Placement Position *</label>
                    <select name="position" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                        <option value="hero_main">Hero Main Slider</option>
                        <option value="promo_strip">Promo Strip</option>
                        <option value="collection_banner">Collection Feature Banner</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition">
                    Create Banner
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

