@extends('admin.layouts.app')

@section('title', 'Email Delivery Logs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Email Delivery Logs</h1>
            <p class="text-xs text-slate-500 mt-1">Audit log of transactional emails dispatched to customers and administrators.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600">Dispatched Mail Records</h3>
            <span class="text-xs text-slate-400 font-mono">Showing {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold">
                        <th class="p-3">Sent At</th>
                        <th class="p-3">Recipient</th>
                        <th class="p-3">Subject Line</th>
                        <th class="p-3">Mailable Type</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="p-3 font-medium text-slate-800">
                                {{ $log->recipient_email }}
                            </td>
                            <td class="p-3 text-slate-900 font-semibold">
                                {{ $log->subject }}
                            </td>
                            <td class="p-3 text-slate-500 font-mono text-[11px]">
                                {{ class_basename($log->mailable_class) }}
                            </td>
                            <td class="p-3">
                                @if($log->status === 'sent')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Delivered
                                    </span>
                                @elseif($log->status === 'queued')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Queued
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $log->error_message }}">
                                        Failed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">No emails logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

