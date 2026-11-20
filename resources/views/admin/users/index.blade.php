@extends('layouts.admin')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
<div class="space-y-6 max-w-7xl">

    <!-- Search Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
        <div>
            <h3 class="text-base font-bold text-white">Platform User Accounts</h3>
            <p class="text-xs text-slate-400">Search users by username, full name, or email. (Password hash is never exposed)</p>
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center space-x-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search username, email..."
                class="px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-slate-950 rounded-xl text-xs font-bold shadow-md transition-all">
                Search
            </button>
            @if($search)
                <a href="{{ route('admin.users.index') }}" class="p-2 text-slate-400 hover:text-white text-xs">Clear</a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Email</th>
                        <th class="py-3.5 px-6">Role</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Registered</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-slate-500">#{{ $user->user_id }}</td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-white">{{ $user->username }}</div>
                                <div class="text-[11px] text-slate-400">{{ $user->full_name }}</div>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-300">{{ $user->email }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] {{ $user->role === 'admin' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-slate-800 text-slate-300' }}">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @if($user->account_status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Active
                                    </span>
                                @elseif($user->account_status === 'suspended')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Suspended
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-700 text-slate-400">
                                        Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-400">{{ $user->created_date->format('Y-m-d') }}</td>
                            <td class="py-4 px-6 text-right">
                                @if($user->user_id !== auth()->id())
                                    @if($user->account_status === 'active')
                                        <form action="{{ route('admin.users.suspend', $user->user_id) }}" method="POST" class="inline" onsubmit="return confirm('Suspend user {{ $user->username }}?')">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-medium transition-all">
                                                Suspend
                                            </button>
                                        </form>
                                    @elseif($user->account_status === 'suspended')
                                        <form action="{{ route('admin.users.reactivate', $user->user_id) }}" method="POST" class="inline" onsubmit="return confirm('Reactivate user {{ $user->username }}?')">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-xs font-medium transition-all">
                                                Reactivate
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-500 italic">(You)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No users match your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
