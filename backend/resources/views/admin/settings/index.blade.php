@extends('admin.layouts.app')

@section('title', 'Settings')
@section('page_title', 'Storefront Configuration & Business Rules')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded-xl border border-slate-200 shadow-sm text-xs">
    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- General Info -->
        <div>
            <h4 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <i class="fa-solid fa-store text-slate-400"></i>
                <span>General Store Identity</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Store Brand Name</label>
                    <input type="text" name="store_name" value="{{ \App\Models\Setting::get('store_name', 'WearDay') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Store Tagline</label>
                    <input type="text" name="store_tagline" value="{{ \App\Models\Setting::get('store_tagline') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Support Email</label>
                    <input type="email" name="store_email" value="{{ \App\Models\Setting::get('store_email') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Support Phone</label>
                    <input type="text" name="store_phone" value="{{ \App\Models\Setting::get('store_phone') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div class="md:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Store & Warehouse Address</label>
                    <input type="text" name="store_address" value="{{ \App\Models\Setting::get('store_address') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
            </div>
        </div>

        <!-- Localization & Currency -->
        <div>
            <h4 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <i class="fa-solid fa-globe text-slate-400"></i>
                <span>Localization & Currency</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Base Currency Code</label>
                    <input type="text" name="currency" value="{{ \App\Models\Setting::get('currency', 'USD') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 uppercase">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ \App\Models\Setting::get('currency_symbol', '$') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Operational Timezone</label>
                    <input type="text" name="timezone" value="{{ \App\Models\Setting::get('timezone', 'America/New_York') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
            </div>
        </div>

        <!-- Shipping & Tax -->
        <div>
            <h4 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <i class="fa-solid fa-calculator text-slate-400"></i>
                <span>Checkout Rates & Thresholds</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Sales Tax Percentage (%)</label>
                    <input type="number" step="0.01" name="tax_rate_percentage" value="{{ \App\Models\Setting::get('tax_rate_percentage', 8.00) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Free Shipping Threshold ($)</label>
                    <input type="number" step="0.01" name="free_shipping_threshold" value="{{ \App\Models\Setting::get('free_shipping_threshold', 50.00) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Flat Rate Shipping Fee ($)</label>
                    <input type="number" step="0.01" name="flat_shipping_rate" value="{{ \App\Models\Setting::get('flat_shipping_rate', 10.00) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
            </div>
        </div>

        <!-- Returns Policy -->
        <div>
            <h4 class="font-bold text-slate-900 text-sm mb-3 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <i class="fa-solid fa-rotate-left text-slate-400"></i>
                <span>Returns & Exchanges Policy</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Eligible Return Window (Days)</label>
                    <input type="number" name="return_window_days" value="{{ \App\Models\Setting::get('return_window_days', 30) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-slate-950 hover:bg-slate-800 text-white font-semibold rounded-lg transition">
                Save Store Settings
            </button>
        </div>
    </form>
</div>
@endsection

