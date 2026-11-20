@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Welcome back, ' . (auth()->user()->username ?? 'Admin'))

@section('content')
<div class="space-y-8 max-w-7xl">

    <!-- METRICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- CARD 1: Users -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-indigo-500/50 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Users</p>
                    <h3 data-count-up class="text-3xl font-extrabold text-white mt-1.5 tabular-nums">{{ number_format($users['total']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span class="{{ $users['active'] > 0 ? 'text-emerald-400 font-medium' : 'text-slate-500' }}">{{ $users['active'] }} Active</span>
                <span class="{{ $users['suspended'] > 0 ? 'text-rose-400 font-medium' : 'text-slate-500' }}">{{ $users['suspended'] }} Suspended</span>
                <span class="text-slate-500">{{ $users['new_today'] }} New Today</span>
            </div>
        </div>

        <!-- CARD 2: Titles -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-indigo-500/50 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Catalog Titles</p>
                    <h3 data-count-up class="text-3xl font-extrabold text-white mt-1.5 tabular-nums">{{ number_format($titles['total']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                @if($titles['pending'] > 0)
                    <span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 font-semibold border border-amber-500/20">
                        {{ $titles['pending'] }} Pending Review
                    </span>
                @else
                    <span class="text-slate-400">0 Pending</span>
                @endif
                <span class="text-slate-500">{{ $titles['hidden'] }} Hidden</span>
            </div>
        </div>

        <!-- CARD 3: Community Content -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-indigo-500/50 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reviews & Topics</p>
                    <h3 data-count-up class="text-3xl font-extrabold text-white mt-1.5 tabular-nums">{{ number_format($content['reviews_active'] + $content['topics_active']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span>{{ $content['reviews_active'] }} Reviews</span>
                <span>{{ $content['topics_active'] }} Topics</span>
                <span class="{{ ($content['reviews_moderated'] + $content['topics_moderated']) > 0 ? 'text-rose-400 font-medium' : 'text-slate-500' }}">{{ $content['reviews_moderated'] + $content['topics_moderated'] }} Soft-deleted</span>
            </div>
        </div>

        <!-- CARD 4: Pending Reports -->
        <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl p-5 shadow-lg relative overflow-hidden group hover:border-indigo-500/50 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reports Queue</p>
                    <h3 data-count-up class="text-3xl font-extrabold text-white mt-1.5 tabular-nums">{{ number_format($reports['pending']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span class="{{ $reports['under_review'] > 0 ? 'text-amber-400 font-medium' : 'text-slate-500' }}">{{ $reports['under_review'] }} Under Review</span>
                <span class="{{ $reports['resolved'] > 0 ? 'text-emerald-400 font-medium' : 'text-slate-500' }}">{{ $reports['resolved'] }} Resolved</span>
                <span class="text-slate-500">{{ $reports['rejected'] }} Rejected</span>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS ROUTING (Flowchart Page 1: "Navigate to another admin module") -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center space-x-2">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span>Quick Navigation to Administrative Modules</span>
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('admin.titles.index') }}?status=pending" class="px-4 py-3 bg-slate-800/70 hover:bg-indigo-600/20 border border-slate-700/60 hover:border-indigo-500/40 rounded-xl text-xs font-semibold text-slate-200 hover-lift flex items-center justify-between group">
                <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                    <span>Pending Titles</span>
                </span>
                <span class="text-indigo-400 group-hover:translate-x-0.5 transition-transform">→</span>
            </a>
            <a href="{{ route('admin.reports.index') }}" class="px-4 py-3 bg-slate-800/70 hover:bg-indigo-600/20 border border-slate-700/60 hover:border-indigo-500/40 rounded-xl text-xs font-semibold text-slate-200 hover-lift flex items-center justify-between group">
                <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Investigate Reports</span>
                </span>
                <span class="text-indigo-400 group-hover:translate-x-0.5 transition-transform">→</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-3 bg-slate-800/70 hover:bg-indigo-600/20 border border-slate-700/60 hover:border-indigo-500/40 rounded-xl text-xs font-semibold text-slate-200 hover-lift flex items-center justify-between group">
                <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>User Accounts</span>
                </span>
                <span class="text-indigo-400 group-hover:translate-x-0.5 transition-transform">→</span>
            </a>
            <a href="{{ route('admin.moderation.index') }}" class="px-4 py-3 bg-slate-800/70 hover:bg-indigo-600/20 border border-slate-700/60 hover:border-indigo-500/40 rounded-xl text-xs font-semibold text-slate-200 hover-lift flex items-center justify-between group">
                <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Content Moderation</span>
                </span>
                <span class="text-indigo-400 group-hover:translate-x-0.5 transition-transform">→</span>
            </a>
        </div>
    </div>

    <!-- RECENT AUDIT LOGS TABLE (Flowchart Module 01: "admin_activity_logs") -->
    <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <h4 class="text-base font-bold text-white tracking-tight flex items-center space-x-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Recent Administrative Audit Logs</span>
            </h4>
            <a href="{{ route('admin.logs.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">View All Logs →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-6">Log ID</th>
                        <th class="py-3 px-6">Admin User</th>
                        <th class="py-3 px-6">Activity Type</th>
                        <th class="py-3 px-6">Description</th>
                        <th class="py-3 px-6 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($recent_logs as $log)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-6 font-mono text-slate-500">#{{ $log->log_id }}</td>
                            <td class="py-3.5 px-6 font-semibold text-white">{{ $log->admin->username ?? 'System' }}</td>
                            <td class="py-3.5 px-6">
                                <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-mono text-[11px]">
                                    {{ $log->activity_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-slate-300 max-w-md truncate">{{ $log->description }}</td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-400">{{ $log->created_date->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">No activity logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
