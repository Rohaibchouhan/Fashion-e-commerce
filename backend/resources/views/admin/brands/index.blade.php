@extends('admin.layouts.app')

@section('title', 'Brands')
@section('page_title', 'Brand Partners & Designer Labels')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                Brands & Labels
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Brand Name</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">Products</th>
                            <th class="px-4 py-3">Website</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($brands as $b)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $b->name }}</td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-500">{{ $b->slug }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $b->products_count }} garments</td>
                            <td class="px-4 py-3 text-indigo-600">
                                @if($b->website)
                                <a href="{{ $b->website }}" target="_blank" class="hover:underline">{{ $b->website }}</a>
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.brands.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete brand?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-slate-900 text-sm mb-4">Add Brand Partner</h4>
            <form action="{{ route('admin.brands.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Brand Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Website URL</label>
                    <input type="url" name="website" placeholder="https://..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Brand Bio / Atelier Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition">
                    Register Brand
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

