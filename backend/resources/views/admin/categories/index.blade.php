@extends('admin.layouts.app')

@section('title', 'Categories')
@section('page_title', 'Category Architecture & Hierarchy')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Category List -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                Categories List
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Category Name</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3">Parent</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $cat)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900 flex items-center space-x-2">
                                @if($cat->image)
                                <img src="{{ $cat->image }}" class="w-7 h-7 object-cover rounded bg-slate-100">
                                @endif
                                <span>{{ $cat->parent_id ? '— ' . $cat->name : $cat->name }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-500">{{ $cat->slug }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ $cat->gender }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $cat->parent->name ?? 'Root' }}</td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
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

        <!-- Right: Create Category Form -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-slate-900 text-sm mb-4">Add New Category</h4>
            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Parent Category</label>
                    <select name="parent_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                        <option value="">None (Top-Level Category)</option>
                        @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Gender Segment *</label>
                    <select name="gender" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                        <option value="all">All / Universal</option>
                        <option value="men">Men</option>
                        <option value="women">Women</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Cover Image URL</label>
                    <input type="url" name="image" placeholder="https://..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Short Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition">
                    Create Category
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

