@extends('admin.layouts.app')

@section('title', 'User: ' . $user->name)
@section('page_title', 'User Details: ' . $user->name)

@section('content')
<div class="space-y-6 text-xs" x-data="{ deleteModalOpen: false }">

    <!-- Top Navigation & Action Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.users') }}" class="text-slate-500 hover:text-slate-900 transition flex items-center gap-1.5 font-medium mb-1">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Users List</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $user->name }}</h1>
                @if($user->status === 'active')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active
                    </span>
                @elseif($user->status === 'suspended')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        Suspended
                    </span>
                @elseif($user->status === 'pending_activation')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        Pending Verification
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Inactive
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $user->email }} &bull; Registered {{ $user->created_at->format('M d, Y h:ia') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Edit Button -->
            <a href="{{ route('admin.users.edit', $user->id) }}" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-pen-to-square text-xs text-indigo-600"></i>
                <span>Edit User</span>
            </a>

            <!-- Status Toggle -->
            @if($user->id !== auth()->id() && (!$user->isSuperAdmin() || $user->status !== 'active'))
            <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg font-semibold transition shadow-xs flex items-center gap-1.5
                    {{ $user->status === 'active' ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                    <i class="fa-solid {{ $user->status === 'active' ? 'fa-ban' : 'fa-circle-check' }} text-xs"></i>
                    <span>{{ $user->status === 'active' ? 'Suspend Account' : 'Reactivate Account' }}</span>
                </button>
            </form>
            @endif

            <!-- Delete User -->
            @if($user->id !== auth()->id())
            <button type="button" @click="deleteModalOpen = true" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg font-semibold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-trash text-xs"></i>
                <span>Delete</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Overview Grids -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1: Identity & Profile -->
        <div class="space-y-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-user-circle text-slate-700"></i>
                    <span>Identity Overview</span>
                </h3>
                <div class="space-y-2.5 text-slate-700">
                    <div><span class="text-slate-400">Full Name:</span> <strong class="text-slate-900">{{ $user->name }}</strong></div>
                    <div><span class="text-slate-400">Email:</span> <span class="font-mono text-slate-900">{{ $user->email }}</span></div>
                    <div><span class="text-slate-400">Phone:</span> <span class="font-mono text-slate-900">{{ $user->phone ?: 'Not provided' }}</span></div>
                    <div>
                        <span class="text-slate-400">Email Verification:</span>
                        @if($user->email_verified_at)
                            <span class="text-emerald-700 font-semibold flex items-center gap-1 inline-flex">
                                <i class="fa-solid fa-circle-check text-xs"></i> Verified ({{ $user->email_verified_at->format('M d, Y') }})
                            </span>
                        @else
                            <span class="text-amber-600 font-semibold">Unverified</span>
                        @endif
                    </div>
                    <div><span class="text-slate-400">Registered:</span> {{ $user->created_at->format('M d, Y H:i') }}</div>
                    <div><span class="text-slate-400">Last Modified:</span> {{ $user->updated_at->format('M d, Y H:i') }}</div>
                </div>
            </div>

            <!-- Roles & Permissions Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
                <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                    <span>Roles & Privileges</span>
                </h3>

                <div class="space-y-2">
                    @foreach($user->roles as $r)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
                        <div class="font-bold text-slate-900 flex items-center justify-between">
                            <span>{{ $r->name }}</span>
                            <span class="font-mono text-[10px] text-slate-400">{{ $r->slug }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $r->description ?: 'Assigned security role' }}</p>

                        @if($r->permissions->isNotEmpty())
                        <div class="mt-2 pt-2 border-t border-slate-200/60 flex flex-wrap gap-1">
                            @foreach($r->permissions as $perm)
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 text-slate-600 rounded text-[9px] font-mono">
                                    {{ $perm->slug }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Column 2 & 3: Address Book & Orders -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Saved Address Book -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-indigo-600"></i>
                        <span>Customer Address Book ({{ $user->addresses->count() }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse($user->addresses as $addr)
                    <div class="p-3.5 border rounded-lg bg-slate-50/70 border-slate-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900">{{ $addr->first_name }} {{ $addr->last_name }}</span>
                            <div class="flex items-center gap-1">
                                @if($addr->is_default_shipping)
                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 font-semibold rounded text-[9px]">Default Shipping</span>
                                @endif
                                @if($addr->is_default_billing)
                                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-800 font-semibold rounded text-[9px]">Default Billing</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-slate-600 leading-relaxed text-[11px]">
                            {{ $addr->street }} {{ $addr->apartment }}<br>
                            {{ $addr->city }}, {{ $addr->state }} {{ $addr->zip_code }}<br>
                            {{ $addr->country }}
                        </div>
                        @if($addr->phone)
                        <div class="text-slate-400 font-mono text-[10px] pt-1">
                            <i class="fa-solid fa-phone text-slate-400 mr-1"></i>{{ $addr->phone }}
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="col-span-2 py-6 text-center text-slate-400 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                        No saved delivery addresses on file for this user.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Customer Order History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-bag-shopping text-slate-700"></i>
                        <span>Order History ({{ $user->orders->count() }})</span>
                    </h3>
                    <span class="font-mono text-slate-600 font-bold">
                        Lifetime Spend: ${{ number_format($user->orders->sum('total'), 2) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                                <th class="p-3">Order Number</th>
                                <th class="p-3">Items</th>
                                <th class="p-3">Total</th>
                                <th class="p-3">Payment</th>
                                <th class="p-3">Fulfillment</th>
                                <th class="p-3">Date</th>
                                <th class="p-3 text-right">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($user->orders as $o)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="p-3 font-mono font-bold text-slate-900">{{ $o->order_number }}</td>
                                <td class="p-3 text-slate-600">{{ $o->items->sum('quantity') }} items</td>
                                <td class="p-3 font-semibold text-slate-900">${{ number_format($o->total, 2) }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $o->payment_status->value === 'paid' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                                        {{ strtoupper($o->payment_status->value) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-800">
                                        {{ $o->status->label() }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-500 text-[11px] font-mono">{{ $o->created_at->format('M d, Y') }}</td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('admin.orders.show', $o->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400">
                                    No purchase orders placed by this account yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity Audit Trail -->
            @if($user->activityLogs->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-3">
                <h3 class="font-bold text-sm text-slate-900 border-b border-slate-100 pb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-slate-500"></i>
                    <span>Recent Security & Activity Logs</span>
                </h3>
                <div class="space-y-2">
                    @foreach($user->activityLogs as $log)
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg text-[11px]">
                        <div>
                            <span class="font-mono font-semibold text-slate-800">[{{ $log->action }}]</span>
                            <span class="text-slate-600 ml-1">{{ $log->description }}</span>
                        </div>
                        <span class="text-slate-400 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

    <!-- Safe Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center space-x-3 text-rose-600">
                <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Delete Account: {{ $user->name }}</h3>
                    <p class="text-[11px] text-slate-500">Safe deletion checks will be executed</p>
                </div>
            </div>

            <p class="text-xs text-slate-600">
                Are you sure you want to remove <strong class="text-slate-900">{{ $user->name }}</strong>?
            </p>

            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <i class="fa-solid fa-shield-halved text-amber-600 mr-1"></i>
                <strong>Order Integrity Guarantee:</strong> If this account has historical purchases, all invoices, order line items, and addresses remain permanently preserved in MySQL.
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition font-semibold text-xs">
                    Cancel
                </button>
                <form action="{{ route('admin.users.destroy', $user->id, false) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-semibold text-xs transition shadow-xs">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
