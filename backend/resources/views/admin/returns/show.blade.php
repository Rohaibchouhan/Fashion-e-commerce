@extends('admin.layouts.app')

@section('title', 'RMA ' . $return->rma_number)
@section('page_title', 'Return RMA Review: ' . $return->rma_number)

@section('content')
<div class="space-y-6 text-xs" x-data="{ rejectModal: false, inspectModal: false }">
    <!-- Top Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.returns') }}" class="text-slate-400 hover:text-slate-700">&larr; Back to Returns</a>
            <span class="text-slate-300">|</span>
            <span class="font-bold text-slate-900 text-sm font-mono">{{ $return->rma_number }}</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-900 text-white">
                {{ $return->status->label() }}
            </span>
            <span class="text-slate-500 font-medium">Order #{{ $return->order->order_number }}</span>
        </div>

        <div class="flex items-center space-x-3">
            @if($return->status->value === 'requested' || $return->status->value === 'under_review')
            <form action="{{ route('admin.returns.approve', $return->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg font-semibold transition">
                    Approve Return & Issue Label
                </button>
            </form>
            <button @click="rejectModal = true" class="px-3.5 py-1.5 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg font-semibold transition">
                Reject Return
            </button>
            @endif

            @if(in_array($return->status->value, ['approved', 'pickup_scheduled', 'in_transit', 'received']) && !$return->inspected_at)
            <button @click="inspectModal = true" class="px-3.5 py-1.5 bg-indigo-700 hover:bg-indigo-800 text-white rounded-lg font-semibold transition">
                Record Quality Inspection
            </button>
            @endif

            @if($return->inspected_at && $return->status->value !== 'refunded' && $return->resolution === 'refund')
            <form action="{{ route('admin.returns.refund', $return->id) }}" method="POST" onsubmit="return confirm('Process refund of ${{ number_format($return->refund_amount, 2) }}?')">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-slate-950 hover:bg-slate-800 text-white rounded-lg font-bold transition">
                    Execute Refund (${{ number_format($return->refund_amount, 2) }})
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- RMA Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Returned Items & Evidence -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 font-bold text-slate-900 flex justify-between items-center">
                    <span>Items Claimed for Return ({{ $return->items->count() }})</span>
                    <span class="text-slate-500 font-normal">Requested Resolution: <strong class="text-slate-900 uppercase">{{ $return->resolution }}</strong></span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($return->items as $item)
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img src="{{ $item->image_url ?? 'https://placehold.co/80' }}" class="w-12 h-12 object-cover rounded bg-slate-100 flex-shrink-0">
                                <div>
                                    <div class="font-bold text-slate-900 text-sm">{{ $item->product_name }}</div>
                                    <div class="text-slate-500 text-[11px]">
                                        Size: {{ $item->selected_size ?? 'N/A' }} &bull; 
                                        Color: {{ $item->selected_color ?? 'N/A' }} &bull; 
                                        Returning: <span class="font-bold text-slate-900">{{ $item->quantity }} units</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-slate-900 text-sm">${{ number_format($item->unit_price * $item->quantity, 2) }}</div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ strtoupper(str_replace('_', ' ', $item->reason)) }}
                                </span>
                            </div>
                        </div>

                        @if($item->customer_comment)
                        <div class="bg-slate-50 p-2.5 rounded-lg text-slate-700 italic border border-slate-100">
                            "{{ $item->customer_comment }}"
                        </div>
                        @endif

                        @if($item->images->count() > 0)
                        <div>
                            <div class="font-semibold text-slate-600 mb-1">Customer Uploaded Photos:</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($item->images as $img)
                                <a href="{{ $img->url }}" target="_blank">
                                    <img src="{{ $img->url }}" class="w-16 h-16 object-cover rounded border border-slate-200 hover:opacity-80 transition">
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($item->inspection_result !== 'pending')
                        <div class="text-[11px] font-semibold text-slate-600">
                            Inspection Status: 
                            <span class="{{ $item->inspection_result === 'resellable' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ strtoupper($item->inspection_result) }} 
                                @if($item->restocked_quantity > 0)
                                (Restocked {{ $item->restocked_quantity }} units to inventory)
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Customer Details & Timeline -->
        <div class="space-y-6">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <h4 class="font-bold text-slate-900">Return Logistics & RMA Information</h4>
                <div class="space-y-1.5 text-slate-600">
                    <div><strong>Customer:</strong> {{ $return->user->name }} ({{ $return->user->email }})</div>
                    <div><strong>Original Order:</strong> <a href="{{ route('admin.orders.show', $return->order_id) }}" class="text-indigo-600 underline font-mono">{{ $return->order->order_number }}</a></div>
                    <div><strong>Tracking:</strong> {{ $return->tracking_number ?? 'Not yet issued' }}</div>
                    @if($return->label_url)
                    <div>
                        <a href="{{ $return->label_url }}" target="_blank" class="text-indigo-600 font-semibold underline flex items-center space-x-1">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>View Return Shipping Label</span>
                        </a>
                    </div>
                    @endif
                    <div><strong>Total Refund Value:</strong> <span class="font-bold text-slate-900 text-sm">${{ number_format($return->refund_amount, 2) }}</span></div>
                </div>
            </div>

            <!-- RMA Stepper Progression -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h4 class="font-bold text-slate-900 mb-3">Return Workflow Stages</h4>
                <div class="space-y-3">
                    @foreach($return->timeline as $event)
                    <div class="flex items-center space-x-2.5">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] {{ $event['completed'] ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-500' }}">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium {{ $event['completed'] ? 'text-slate-900 font-bold' : 'text-slate-400' }}">{{ $event['label'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <!-- Reject Return Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 text-xs" @click.away="rejectModal = false">
            <h3 class="font-bold text-slate-900 text-sm mb-3">Decline Return Request</h3>
            <form action="{{ route('admin.returns.reject', $return->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reason for Rejection *</label>
                    <textarea name="rejection_reason" rows="3" required placeholder="State why this return request was declined..." class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:outline-none"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="rejectModal = false" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg">Decline RMA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quality Inspection Modal -->
    <div x-show="inspectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 text-xs" @click.away="inspectModal = false">
            <h3 class="font-bold text-slate-900 text-sm mb-1">Atelier Quality Inspection</h3>
            <p class="text-slate-500 mb-4">Select condition for each item. Resellable items will be automatically restocked into inventory.</p>

            <form action="{{ route('admin.returns.inspect', $return->id) }}" method="POST" class="space-y-4">
                @csrf
                @foreach($return->items as $item)
                <div class="p-3 border border-slate-200 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-800">{{ $item->product_name }}</div>
                        <div class="text-[11px] text-slate-400">Qty: {{ $item->quantity }}</div>
                    </div>
                    <select name="results[{{ $item->id }}][condition]" class="px-3 py-1.5 rounded-lg border border-slate-300 focus:outline-none font-semibold">
                        <option value="resellable">Resellable (Restock to inventory)</option>
                        <option value="damaged">Damaged / Flawed (Do not restock)</option>
                        <option value="scrap">Scrap / Write-off</option>
                    </select>
                </div>
                @endforeach

                <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="inspectModal = false" class="px-4 py-2 border border-slate-300 rounded-lg">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg">Save Inspection & Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

