@extends('admin.layouts.app')

@section('title', 'Product Reviews')
@section('page_title', 'Customer Reviews & Feedback Moderation')

@section('content')
<div class="space-y-5 text-xs">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Garment</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Rating</th>
                        <th class="px-4 py-3">Review</th>
                        <th class="px-4 py-3">Verified Purchase</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Moderation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reviews as $rev)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">{{ $rev->product->name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $rev->product->sku }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-800">{{ $rev->user->name }}</td>
                        <td class="px-4 py-3">
                            <span class="text-amber-500 font-semibold">{{ $rev->rating }}/5 ★</span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <div class="font-bold text-slate-900">{{ $rev->title }}</div>
                            <div class="text-slate-600 truncate">{{ $rev->comment }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($rev->is_verified_purchase)
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Verified</span>
                            @else
                            <span class="text-slate-400 text-[11px]">Unverified</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $rev->is_approved ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $rev->is_approved ? 'Published' : 'Pending Moderation' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if(!$rev->is_approved)
                            <form action="{{ route('admin.reviews.approve', $rev->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-800 text-white rounded text-[11px] font-medium">Approve</button>
                            </form>
                            @else
                            <form action="{{ route('admin.reviews.reject', $rev->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded text-[11px] font-medium">Hide</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No customer reviews submitted yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection

