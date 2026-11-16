@extends('admin.layouts.app')

@section('title', 'Coupons')
@section('page_title', 'Discount Coupons & Promotional Campaigns')

@section('content')
<div class="space-y-6 text-xs">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 font-bold text-slate-900">
                Active Promotional Coupons
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Min Spend</th>
                            <th class="px-4 py-3">Redemptions</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($coupons as $coup)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $coup->code }}</td>
                            <td class="px-4 py-3 font-semibold text-emerald-700">
                                {{ $coup->type === 'percentage' ? "{$coup->value}% OFF" : "\${$coup->value} OFF" }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">${{ number_format($coup->min_spend ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600 font-medium">{{ $coup->used_count }} used</td>
                            <td class="px-4 py-3">
                                <form action="{{ route('admin.coupons.toggle', $coup->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $coup->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $coup->is_active ? 'Active' : 'Disabled' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.coupons.destroy', $coup->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete coupon?')">
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
            <h4 class="font-bold text-slate-900 text-sm mb-4">Create Promo Code</h4>
            <form action="{{ route('admin.coupons.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Coupon Code *</label>
                    <input type="text" name="code" placeholder="e.g. SUMMER25" required class="w-full px-3 py-2 rounded-lg border border-slate-300 uppercase font-mono focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Discount Type *</label>
                        <select name="type" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Cash ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Value *</label>
                        <input type="number" step="0.01" name="value" required placeholder="e.g. 15" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Min Spend ($)</label>
                        <input type="number" step="0.01" name="min_spend" placeholder="0.00" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Max Discount ($)</label>
                        <input type="number" step="0.01" name="max_discount" placeholder="No limit" class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Per-Customer Limit</label>
                    <input type="number" name="usage_limit_per_customer" value="1" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Description *</label>
                    <input type="text" name="description" placeholder="e.g. 15% off first wardrobe order" required class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none">
                </div>

                <button type="submit" class="w-full py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition">
                    Activate Coupon
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

