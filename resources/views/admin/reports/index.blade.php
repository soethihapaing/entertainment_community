@extends('layouts.admin')

@section('title', 'Reports Management')
@section('page_title', 'Report Management')

@section('content')
<div class="space-y-6 max-w-7xl">

    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
        <div class="flex items-center space-x-2 text-xs overflow-x-auto">
            <a href="{{ route('admin.reports.index') }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ empty($status) ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                All Reports
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $status === 'pending' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Pending
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'under_review']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $status === 'under_review' ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Under Review
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $status === 'resolved' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Resolved
            </a>
            <a href="{{ route('admin.reports.index', ['status' => 'rejected']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $status === 'rejected' ? 'bg-slate-700 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Rejected
            </a>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Reporter</th>
                        <th class="py-3.5 px-6">Target Type</th>
                        <th class="py-3.5 px-6">Target ID</th>
                        <th class="py-3.5 px-6">Reason</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($reports as $report)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-slate-500">#{{ $report->report_id }}</td>
                            <td class="py-4 px-6 font-semibold text-white">{{ $report->reporter->username ?? 'Anonymous' }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] uppercase tracking-wider bg-slate-800 text-amber-400 border border-slate-700">
                                    {{ str_replace('_', ' ', $report->target_type) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-400">#{{ $report->target_id }}</td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-white">{{ $report->report_reason }}</div>
                                <div class="text-[11px] text-slate-400 max-w-xs truncate">{{ $report->report_description ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                @if($report->report_status === 'resolved')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Resolved</span>
                                @elseif($report->report_status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-800 text-slate-400">Rejected</span>
                                @elseif($report->report_status === 'under_review')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Under Review</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 inline-flex items-center gap-1.5"><span class="ping-dot"></span>Pending</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-1.5">
                                @if(in_array($report->report_status, ['pending', 'under_review']))
                                    <!-- Resolve & Soft-delete content -->
                                    @if($report->target_type !== 'user')
                                        <form action="{{ route('admin.reports.resolve', $report->report_id) }}" method="POST" class="inline" onsubmit="return confirm('Soft-delete content and resolve report?')">
                                            @csrf
                                            <input type="hidden" name="action" value="soft_delete_content">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-medium">
                                                Delete Content
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Resolve & Suspend user -->
                                    <form action="{{ route('admin.reports.resolve', $report->report_id) }}" method="POST" class="inline" onsubmit="return confirm('Suspend user and resolve report?')">
                                        @csrf
                                        <input type="hidden" name="action" value="suspend_user">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 text-[11px] font-medium">
                                            Suspend User
                                        </button>
                                    </form>

                                    <!-- Reject (No violation) -->
                                    <form action="{{ route('admin.reports.reject', $report->report_id) }}" method="POST" class="inline" onsubmit="return confirm('Reject report (no violation)?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px]">
                                            Reject
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500 italic">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No reports yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
