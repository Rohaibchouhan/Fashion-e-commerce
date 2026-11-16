@extends('admin.layouts.app')

@section('title', 'Pages & FAQs')
@section('page_title', 'Policy Pages & FAQ Management')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Pages Column -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm">Static Policy Pages</h4>
            <div class="divide-y divide-slate-100">
                @foreach($pages as $page)
                <div class="py-2.5 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $page->title }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">/pages/{{ $page->slug }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800">Published</span>
                </div>
                @endforeach
            </div>

            <div class="pt-4 border-t border-slate-100">
                <h5 class="font-bold text-slate-800 mb-2">Create New Page</h5>
                <form action="{{ route('admin.cms.pages.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Page Title *</label>
                        <input type="text" name="title" required class="w-full px-3 py-1.5 rounded border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Page Content (HTML/Markdown) *</label>
                        <textarea name="content" rows="3" required class="w-full px-3 py-1.5 rounded border border-slate-300"></textarea>
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white rounded font-medium">Create Page</button>
                </form>
            </div>
        </div>

        <!-- FAQs Column -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm">Customer FAQs ({{ $faqs->count() }})</h4>
            <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                @foreach($faqs as $faq)
                <div class="py-2.5">
                    <div class="font-semibold text-slate-900 flex justify-between">
                        <span>{{ $faq->question }}</span>
                        <span class="text-[10px] text-slate-400 uppercase font-mono">{{ $faq->category }}</span>
                    </div>
                    <div class="text-slate-600 mt-1 line-clamp-2">{{ $faq->answer }}</div>
                </div>
                @endforeach
            </div>

            <div class="pt-4 border-t border-slate-100">
                <h5 class="font-bold text-slate-800 mb-2">Add New FAQ Question</h5>
                <form action="{{ route('admin.cms.faqs.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Question *</label>
                        <input type="text" name="question" required class="w-full px-3 py-1.5 rounded border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Answer *</label>
                        <textarea name="answer" rows="2" required class="w-full px-3 py-1.5 rounded border border-slate-300"></textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Category *</label>
                        <select name="category" class="w-full px-3 py-1.5 rounded border border-slate-300">
                            <option value="sizing">Sizing & Measurement</option>
                            <option value="shipping">Shipping & Delivery</option>
                            <option value="returns">Returns & Exchanges</option>
                            <option value="orders">Orders & Payments</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-slate-900 text-white rounded font-medium">Add FAQ</button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

