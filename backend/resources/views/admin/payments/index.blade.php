@extends('admin.layouts.app')

@section('title', 'Payments & Gateway Audits')
@section('page_title', 'Payment Management & Gateway Audits')

@section('content')
<div class="space-y-6 text-xs">

    <!-- Flash Notifications -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">&times;</button>
    </div>
    @endif

    <!-- Section Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
        <div class="flex space-x-2">
            <a href="{{ route('admin.payments', ['tab' => 'payments']) }}"
               class="px-4 py-2 rounded-lg font-bold transition flex items-center space-x-2 {{ $tab !== 'test' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-money-bill-wave"></i>
                <span>Customer Payments</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $tab !== 'test' ? 'bg-slate-800 text-slate-200' : 'bg-slate-200 text-slate-700' }}">
                    {{ $realMetrics['paid_count'] + $realMetrics['pending_count'] }}
                </span>
            </a>
            <a href="{{ route('admin.payments', ['tab' => 'test']) }}"
               class="px-4 py-2 rounded-lg font-bold transition flex items-center space-x-2 {{ $tab === 'test' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                <i class="fa-solid fa-vial-circle-check"></i>
                <span>Test &amp; Sandbox Payments</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'test' ? 'bg-amber-700 text-amber-100' : 'bg-amber-100 text-amber-800 font-semibold' }}">
                    TEST / SANDBOX
                </span>
            </a>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase font-bold {{ $isProduction ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                Env: {{ strtoupper($environment) }}
            </span>
        </div>
    </div>

    @if($tab !== 'test')
    <!-- ========================================== -->
    <!-- REAL PAYMENTS OVERVIEW & METRICS           -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-slate-400 font-semibold uppercase text-[10px]">Real Captured Volume</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">${{ number_format($realMetrics['total_volume'], 2) }}</div>
            <div class="text-[10px] text-emerald-700 mt-1 flex items-center">
                <i class="fa-solid fa-circle-check mr-1"></i> Strictly verified live funds
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-slate-400 font-semibold uppercase text-[10px]">Settled / Paid Orders</div>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $realMetrics['paid_count'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Confirmed payments</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-slate-400 font-semibold uppercase text-[10px]">Pending Authorizations</div>
            <div class="text-2xl font-extrabold text-amber-700 mt-1">{{ $realMetrics['pending_count'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Awaiting capture/dispatch</div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div class="text-slate-400 font-semibold uppercase text-[10px]">Failed / Refunded</div>
            <div class="text-2xl font-extrabold text-slate-700 mt-1">{{ $realMetrics['failed_count'] + $realMetrics['refunded_count'] }}</div>
            <div class="text-[10px] text-slate-500 mt-1">Declined or returned</div>
        </div>
    </div>

    @else
    <!-- ========================================== -->
    <!-- TEST / SANDBOX CONTROLS & METRICS          -->
    <!-- ========================================== -->
    <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white uppercase">Sandbox Mode</span>
                    <h3 class="font-bold text-slate-900 text-sm">Test Payment Management & Simulator</h3>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5">
                    Exercise full checkout, payment authorization, and inventory reservation without charging real payment cards.
                    @if($isProduction)
                    <strong class="text-rose-700 font-bold ml-1">STRICTLY DISABLED IN PRODUCTION</strong>
                    @endif
                </p>
            </div>

            <!-- Enable / Disable Toggle Form -->
            @if(!$isProduction)
            <form action="{{ route('admin.payments.test_mode.toggle') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 shadow-xs {{ $testModeEnabled ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                    <i class="fa-solid {{ $testModeEnabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    <span>{{ $testModeEnabled ? 'Disable Test Mode' : 'Enable Test Mode' }}</span>
                </button>
            </form>
            @endif
        </div>

        <!-- Simulator Form -->
        @if(!$isProduction && $testModeEnabled)
        <div class="bg-white p-4 rounded-xl border border-amber-200">
            <div class="font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
                <i class="fa-solid fa-play text-amber-600"></i>
                <span>Simulate Test Payment On Existing Pending Order</span>
            </div>
            @if($pendingOrders->isEmpty())
            <p class="text-slate-500 italic">No pending orders currently available to simulate. Place a test order from the checkout first.</p>
            @else
            <form action="{{ route('admin.payments.simulate') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                @csrf
                <div class="md:col-span-2">
                    <label class="block font-semibold text-slate-700 mb-1">Select Pending Order</label>
                    <select name="order_id" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800">
                        @foreach($pendingOrders as $ord)
                        <option value="{{ $ord->id }}">
                            {{ $ord->order_number }} - {{ $ord->customer->name ?? 'Guest' }} (${{ number_format($ord->total, 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Test Scenario</label>
                    <select name="scenario" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800">
                        <option value="success">✔ Success (Paid & Commit Stock)</option>
                        <option value="failure">✖ Failure (Decline / Preserve Hold)</option>
                        <option value="pending">⏳ Pending (Awaiting Async Webhook)</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition shadow-xs flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Execute Test</span>
                    </button>
                </div>
            </form>
            @endif
        </div>
        @endif

        <!-- Test Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded-lg border border-amber-200">
                <div class="text-slate-400 font-semibold uppercase text-[9px]">Total Test Runs</div>
                <div class="text-lg font-bold text-slate-900">{{ $testMetrics['total_count'] }}</div>
            </div>
            <div class="bg-white p-3 rounded-lg border border-amber-200">
                <div class="text-slate-400 font-semibold uppercase text-[9px]">Simulated Paid</div>
                <div class="text-lg font-bold text-emerald-700">{{ $testMetrics['paid_count'] }}</div>
            </div>
            <div class="bg-white p-3 rounded-lg border border-amber-200">
                <div class="text-slate-400 font-semibold uppercase text-[9px]">Simulated Declines</div>
                <div class="text-lg font-bold text-rose-700">{{ $testMetrics['failed_count'] }}</div>
            </div>
            <div class="bg-white p-3 rounded-lg border border-amber-200">
                <div class="text-slate-400 font-semibold uppercase text-[9px]">Simulated Pending</div>
                <div class="text-lg font-bold text-amber-700">{{ $testMetrics['pending_count'] }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- FILTER BAR                                 -->
    <!-- ========================================== -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form action="{{ route('admin.payments') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Search</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Order #, Txn ID, Customer..."
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg pl-8 pr-2 py-1.5 text-xs text-slate-800">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2 text-slate-400"></i>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Payment Status</label>
                <select name="status" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800">
                    <option value="all">All Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Payment Method</label>
                <select name="method" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-800">
                    <option value="all">All Methods</option>
                    <option value="card" {{ request('method') === 'card' ? 'selected' : '' }}>Credit / Debit Card</option>
                    <option value="stripe" {{ request('method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="cod" {{ request('method') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                    <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Wire</option>
                    <option value="test" {{ request('method') === 'test' ? 'selected' : '' }}>Test Sandbox</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Date Range</label>
                <div class="flex items-center space-x-1">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-1/2 bg-slate-50 border border-slate-300 rounded-lg px-2 py-1.5 text-[11px] text-slate-800">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-1/2 bg-slate-50 border border-slate-300 rounded-lg px-2 py-1.5 text-[11px] text-slate-800">
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition shadow-xs flex items-center justify-center space-x-1">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.payments', ['tab' => $tab]) }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg transition">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- PAYMENTS DATA TABLE                        -->
    <!-- ========================================== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 font-bold text-slate-900 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span>{{ $tab === 'test' ? 'Test Sandbox Payment Records' : 'Authoritative Customer Payments' }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $tab === 'test' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $payments->total() }} records
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Payment ID</th>
                        <th class="px-4 py-3">Order Number</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Transaction Reference</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $pay)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-700">
                            #PAY-{{ str_pad($pay->id, 5, '0', STR_PAD_LEFT) }}
                            @if($pay->is_test)
                            <span class="ml-1 px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-bold rounded">TEST</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono font-bold text-indigo-600">
                            @if($pay->order)
                            <a href="{{ route('admin.orders.show', $pay->order->id) }}" class="hover:underline">
                                {{ $pay->order->order_number }}
                            </a>
                            @else
                            <span class="text-slate-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-800">
                            {{ $pay->order->customer->name ?? 'Guest Customer' }}
                            <div class="text-[10px] text-slate-400">{{ $pay->order->customer->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 capitalize font-medium text-slate-700">
                            <span class="inline-flex items-center space-x-1">
                                @if(in_array($pay->payment_method, ['card', 'stripe']))
                                <i class="fa-solid fa-credit-card text-slate-400"></i>
                                @elseif($pay->payment_method === 'cod')
                                <i class="fa-solid fa-hand-holding-dollar text-slate-400"></i>
                                @elseif($pay->payment_method === 'bank_transfer')
                                <i class="fa-solid fa-building-columns text-slate-400"></i>
                                @else
                                <i class="fa-solid fa-vial text-amber-500"></i>
                                @endif
                                <span>{{ str_replace('_', ' ', $pay->payment_method) }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-slate-500">
                            {{ $pay->transaction_id ?? 'None' }}
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-900">
                            ${{ number_format($pay->amount, 2) }}
                            <span class="text-[10px] text-slate-400 font-normal">{{ $pay->currency }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusVal = is_object($pay->status) ? $pay->status->value : $pay->status;
                            @endphp
                            @if($statusVal === 'paid')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    PAID
                                </span>
                            @elseif($statusVal === 'pending')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    PENDING
                                </span>
                            @elseif($statusVal === 'failed')
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                    FAILED
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-800 border border-purple-200">
                                    {{ strtoupper($statusVal) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ $pay->created_at ? $pay->created_at->format('M d, Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.payments.show', $pay->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-[11px] transition">
                                Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-2xl text-slate-300 mb-2"></i>
                            <div>No payment records found matching the active criteria.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
