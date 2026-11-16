@extends('admin.layouts.app')

@section('title', 'Customer: ' . $customer->name)
@section('page_title', 'Customer Profile: ' . $customer->name)

@section('content')
<div class="space-y-6 text-xs">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.customers') }}" class="text-slate-600 hover:text-slate-900 font-medium">&larr; Back to Customers</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.edit', $customer->id) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square text-xs text-indigo-600"></i>
                <span>Edit Profile</span>
            </a>
            <form action="{{ route('admin.customers.toggle', $customer->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg border font-medium {{ $customer->status === 'active' ? 'border-rose-300 text-rose-700 hover:bg-rose-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                    {{ $customer->status === 'active' ? 'Suspend Account' : 'Reactivate Account' }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Info -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <h4 class="font-bold text-slate-900 text-sm">Account Overview</h4>
            <div class="space-y-1.5 text-slate-700">
                <div><strong>Name:</strong> {{ $customer->name }}</div>
                <div><strong>Email:</strong> {{ $customer->email }}</div>
                <div><strong>Phone:</strong> {{ $customer->phone ?? 'None' }}</div>
                <div><strong>Status:</strong> {{ ucfirst($customer->status) }}</div>
                <div><strong>Preferred Currency:</strong> {{ $customer->customerProfile->preferred_currency ?? 'USD' }}</div>
                <div><strong>Marketing Opt-in:</strong> {{ ($customer->customerProfile->marketing_opt_in ?? false) ? 'Subscribed' : 'No' }}</div>
                <div><strong>Member Since:</strong> {{ $customer->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        <!-- Saved Addresses -->
        <div class="md:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
            <h4 class="font-bold text-slate-900 text-sm">Saved Address Book ({{ $customer->addresses->count() }})</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($customer->addresses as $addr)
                <div class="p-3 border border-slate-200 rounded-lg bg-slate-50">
                    <div class="font-bold text-slate-800">{{ $addr->full_name }}</div>
                    <div class="text-slate-600 mt-1">
                        {{ $addr->street }} {{ $addr->apartment }}<br>
                        {{ $addr->city }}, {{ $addr->state }} {{ $addr->zip_code }}<br>
                        {{ $addr->country }}
                    </div>
                    <div class="mt-2 text-[10px] space-x-1">
                        @if($addr->is_default_shipping)
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 font-semibold rounded">Default Shipping</span>
                        @endif
                        @if($addr->is_default_billing)
                        <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 font-semibold rounded">Default Billing</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-slate-400 py-4">No saved addresses on file.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Order History -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 font-bold text-slate-900 text-sm">
            Order Purchase History ({{ $customer->orders->count() }})
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Total Amount</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3">Fulfillment</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customer->orders as $o)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $o->order_number }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">${{ number_format($o->total, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $o->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                                {{ strtoupper($o->payment_status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-800">
                                {{ $o->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $o->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.orders.show', $o->id) }}" class="text-indigo-600 hover:underline font-medium">View &rarr;</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-400">Customer has not placed any orders yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

