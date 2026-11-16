@extends('admin.layouts.app')

@section('title', 'Blog Management')
@section('page_title', 'Editorial Stories & Fashion Blog')

@section('content')
<div class="space-y-5 text-xs">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Article Title</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Author</th>
                        <th class="px-4 py-3">Views</th>
                        <th class="px-4 py-3">Published Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($posts as $p)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $p->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $p->category->name ?? 'Editorial' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $p->author->name }}</td>
                        <td class="px-4 py-3 font-mono">{{ $p->view_count }} views</td>
                        <td class="px-4 py-3 text-slate-500">{{ $p->published_at ? $p->published_at->format('M d, Y') : 'Draft' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No blog posts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

