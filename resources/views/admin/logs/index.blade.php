@extends('layouts.admin')

@section('title', 'Admin Activity Logs')
@section('page_title', 'Activity Logs')

@section('content')
<div class="space-y-6 max-w-7xl">

    <!-- Filter Form -->
    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block text-slate-400 font-semibold mb-1">Filter by Admin</label>
                <select name="admin_id" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Administrators</option>
                    @foreach($admins as $adm)
                        <option value="{{ $adm->user_id }}" {{ $adminId == $adm->user_id ? 'selected' : '' }}>{{ $adm->username }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-slate-400 font-semibold mb-1">Activity Type</label>
                <input type="text" name="activity_type" value="{{ $activityType }}" placeholder="e.g. SUSPEND_USER, TITLE_APPROVED"
                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs placeholder-slate-500 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-slate-400 font-semibold mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $fromDate }}"
                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white text-xs focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all">
                    Filter Logs
                </button>
                @if($adminId || $activityType || $fromDate || $toDate)
                    <a href="{{ route('admin.logs.index') }}" class="py-2 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Audit Log History</h3>
            <span class="text-xs text-slate-500 font-mono">Immutable Read-Only</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">Log ID</th>
                        <th class="py-3.5 px-6">Admin User</th>
                        <th class="py-3.5 px-6">Activity Type</th>
                        <th class="py-3.5 px-6">Audit Description</th>
                        <th class="py-3.5 px-6 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-slate-500">#{{ $log->log_id }}</td>
                            <td class="py-4 px-6 font-semibold text-white">{{ $log->admin->username ?? 'System/CLI' }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-md font-mono text-[11px] bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ $log->activity_type }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-300 max-w-lg">{{ $log->description }}</td>
                            <td class="py-4 px-6 text-right font-mono text-slate-400">{{ $log->created_date->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">No activity logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
