@extends('admin.layouts.app')

@section('title', 'Activity Audit Logs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Activity Audit Logs</h1>
            <p class="text-xs text-slate-500 mt-1">Immutable audit trail of administrative modifications, inventory shifts, and order updates.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600">Audit Trail</h3>
            <span class="text-xs text-slate-400 font-mono">Showing {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold">
                        <th class="p-3">Timestamp</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">User</th>
                        <th class="p-3">Description</th>
                        <th class="p-3">Entity Subject</th>
                        <th class="p-3">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if($log->user)
                                    <div class="font-medium text-slate-900">{{ $log->user->name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-slate-400 italic">System Automation</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-700 max-w-md">
                                {{ $log->description }}
                            </td>
                            <td class="p-3 text-slate-400 font-mono text-[11px]">
                                @if($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="p-3 text-slate-500 font-mono text-[11px]">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No activity logs recorded yet.</td>
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

