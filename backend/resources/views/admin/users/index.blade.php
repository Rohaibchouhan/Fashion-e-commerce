@extends('admin.layouts.app')

@section('title', 'User Management')
@section('page_title', 'User & Access Management')

@section('content')
<div class="space-y-6 text-xs" x-data="{ deleteModalOpen: false, deleteAction: '', deleteUserName: '' }">

    <!-- Top Action Bar & Stat Counters -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">User Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage system administrators, store staff, and registered customer accounts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold flex items-center gap-1.5 shadow-sm transition">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Add New User</span>
            </a>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.users') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Search User</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, or phone..."
                           class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-900">
                </div>
            </div>

            <!-- Role Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Role</label>
                <select name="role" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-900 bg-white">
                    <option value="all">All Roles</option>
                    <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>All Staff (Admin+)</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Account Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-900 bg-white">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="pending_activation" {{ request('status') === 'pending_activation' ? 'selected' : '' }}>Pending Verification</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sort By</label>
                <select name="sort_by" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-900 bg-white">
                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Registration Date</option>
                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Full Name</option>
                    <option value="email" {{ request('sort_by') === 'email' ? 'selected' : '' }}>Email Address</option>
                    <option value="orders_count" {{ request('sort_by') === 'orders_count' ? 'selected' : '' }}>Orders Count</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold transition">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'role', 'status', 'sort_by', 'date_from', 'date_to']))
                <a href="{{ route('admin.users') }}" class="px-3 py-2 border border-slate-200 hover:bg-slate-100 text-slate-700 rounded-lg font-semibold transition" title="Clear Filters">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-700">Database User Records</span>
            <span class="text-xs text-slate-400 font-mono">{{ $users->total() }} total registered users</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="p-3.5">User</th>
                        <th class="p-3.5">Assigned Role</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Phone</th>
                        <th class="p-3.5">Total Orders</th>
                        <th class="p-3.5">Registered</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- User identity -->
                        <td class="p-3.5">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                    {{ substr($u->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>{{ $u->name }}</span>
                                        @if($u->id === auth()->id())
                                        <span class="px-1.5 py-0.2 text-[9px] bg-slate-200 text-slate-700 rounded font-semibold">You</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>

                        <!-- Role -->
                        <td class="p-3.5">
                            @foreach($u->roles as $r)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border
                                    @if($r->slug === 'super_admin') bg-rose-50 text-rose-700 border-rose-200
                                    @elseif(in_array($r->slug, ['admin', 'store_manager'])) bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($r->slug === 'customer') bg-slate-100 text-slate-700 border-slate-200
                                    @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                    {{ $r->name }}
                                </span>
                            @endforeach
                        </td>

                        <!-- Account Status -->
                        <td class="p-3.5">
                            @if($u->status === 'active')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Active
                                </span>
                            @elseif($u->status === 'suspended')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    Suspended
                                </span>
                            @elseif($u->status === 'pending_activation')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Pending
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <!-- Phone -->
                        <td class="p-3.5 text-slate-600 font-mono text-[11px]">
                            {{ $u->phone ?: '—' }}
                        </td>

                        <!-- Orders -->
                        <td class="p-3.5">
                            <span class="font-semibold text-slate-900">{{ $u->orders_count }}</span>
                            @if($u->orders_sum_total > 0)
                            <span class="text-[11px] text-slate-400 block">${{ number_format($u->orders_sum_total, 2) }}</span>
                            @endif
                        </td>

                        <!-- Registration date -->
                        <td class="p-3.5 text-slate-500 text-[11px] font-mono">
                            {{ $u->created_at->format('M d, Y') }}
                        </td>

                        <!-- Actions -->
                        <td class="p-3.5 text-right space-x-1 whitespace-nowrap">
                            <!-- View Details -->
                            <a href="{{ route('admin.users.show', $u->id) }}" class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded inline-block transition" title="View Details">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>

                            <!-- Edit -->
                            <a href="{{ route('admin.users.edit', $u->id) }}" class="p-1.5 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded inline-block transition" title="Edit User">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </a>

                            <!-- Toggle Status -->
                            @if($u->id !== auth()->id() && (!$u->isSuperAdmin() || $u->status !== 'active'))
                            <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 {{ $u->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded transition"
                                        title="{{ $u->status === 'active' ? 'Suspend Account' : 'Activate Account' }}">
                                    <i class="fa-solid {{ $u->status === 'active' ? 'fa-ban' : 'fa-circle-check' }} text-xs"></i>
                                </button>
                            </form>
                            @endif

                            <!-- Delete User -->
                            @if($u->id !== auth()->id())
                            <button type="button"
                                    @click="deleteModalOpen = true; deleteAction = '{{ route('admin.users.destroy', $u->id, false) }}'; deleteUserName = '{{ addslashes($u->name) }}'"
                                    class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition" title="Delete User">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-400">
                            No users matched your search criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $users->links() }}
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
                    <h3 class="font-bold text-slate-900 text-sm">Confirm User Deletion</h3>
                    <p class="text-[11px] text-slate-500">Action cannot be reversed without database restore</p>
                </div>
            </div>

            <p class="text-xs text-slate-600">
                Are you sure you want to delete user <strong x-text="deleteUserName" class="text-slate-900"></strong>?
            </p>

            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <i class="fa-solid fa-shield-halved text-amber-600 mr-1"></i>
                <strong>Order Integrity Guarantee:</strong> If this account has placed historical orders, order details and billing history are preserved intact while deactivating account access.
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition font-semibold text-xs">
                    Cancel
                </button>
                <form :action="deleteAction" method="POST" class="inline">
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
