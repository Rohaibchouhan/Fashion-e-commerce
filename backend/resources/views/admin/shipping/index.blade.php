@extends('admin.layouts.app')

@section('title', 'Shipping')
@section('page_title', 'Shipping Methods & Carrier Logistics')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($methods as $m)
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 text-sm">{{ $m->name }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $m->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                    {{ $m->is_active ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <p class="text-slate-500">{{ $m->description }}</p>

            <form action="{{ route('admin.shipping.method.update', $m->id) }}" method="POST" class="space-y-3 pt-2 border-t border-slate-100">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Fee ($)</label>
                        <input type="number" step="0.01" name="cost" value="{{ $m->cost }}" required class="w-full px-2.5 py-1.5 rounded border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Free Over ($)</label>
                        <input type="number" step="0.01" name="min_order_free" value="{{ $m->min_order_free }}" placeholder="None" class="w-full px-2.5 py-1.5 rounded border border-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Min Days</label>
                        <input type="number" name="estimated_days_min" value="{{ $m->estimated_days_min }}" class="w-full px-2.5 py-1.5 rounded border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Max Days</label>
                        <input type="number" name="estimated_days_max" value="{{ $m->estimated_days_max }}" class="w-full px-2.5 py-1.5 rounded border border-slate-300">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center space-x-1.5 text-slate-700">
                        <input type="checkbox" name="is_active" value="1" {{ $m->is_active ? 'checked' : '' }} class="rounded border-slate-300">
                        <span>Active</span>
                    </label>
                    <button type="submit" class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded font-medium">Save</button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endsection

