@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page_title', 'Customer Management')

@section('content')
<div class="space-y-5 text-xs" x-data="{ deleteModalOpen: false, deleteAction: '', deleteCustomerName: '' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex-1">
            <form method="GET" class="flex items-center gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer name, email, or phone..."
                       class="px-3 py-2 rounded-lg border border-slate-300 w-full sm:w-80 focus:outline-none focus:ring-1 focus:ring-slate-900">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold transition">Search</button>
                @if(request('search'))
                <a href="{{ route('admin.customers') }}" class="px-3 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg">Clear</a>
                @endif
            </form>
        </div>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-semibold flex items-center gap-2 shadow-xs transition self-start sm:self-auto">
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Add Customer</span>
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-700">Customer Records</span>
            <span class="text-xs text-slate-400 font-mono">{{ $customers->total() }} registered customers</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Total Orders</th>
                        <th class="px-4 py-3">Lifetime Value</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Joined Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">{{ $c->name }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $c->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 font-mono">{{ $c->phone ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $c->orders_count }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">${{ number_format($c->orders_sum_total ?? 0, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $c->status === 'active' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
                                {{ ucfirst($c->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-[11px]">{{ $c->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <a href="{{ route('admin.customers.show', $c->id) }}" class="p-1.5 text-slate-700 hover:bg-slate-100 rounded inline-block transition" title="View Profile">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            <a href="{{ route('admin.users.edit', $c->id) }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded inline-block transition" title="Edit Customer">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </a>
                            <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 {{ $c->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded transition"
                                        title="{{ $c->status === 'active' ? 'Suspend Account' : 'Activate Account' }}">
                                    <i class="fa-solid {{ $c->status === 'active' ? 'fa-ban' : 'fa-circle-check' }} text-xs"></i>
                                </button>
                            </form>
                            <button type="button"
                                    @click="deleteModalOpen = true; deleteAction = '{{ route('admin.customers.destroy', $c->id, false) }}'; deleteCustomerName = '{{ addslashes($c->name) }}'"
                                    class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition" title="Delete Customer">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No customer records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200">
            {{ $customers->links() }}
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
                    <h3 class="font-bold text-slate-900 text-sm">Delete Customer Account</h3>
                    <p class="text-[11px] text-slate-500">Historical order preservation verified</p>
                </div>
            </div>

            <p class="text-xs text-slate-600">
                Are you sure you want to delete customer <strong x-text="deleteCustomerName" class="text-slate-900"></strong>?
            </p>

            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                <i class="fa-solid fa-shield-halved text-amber-600 mr-1"></i>
                <strong>Order Integrity Guarantee:</strong> If this customer has placed past orders, invoices and order line items are permanently retained in MySQL while deactivating account access.
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
