@extends('layouts.admin')

@section('title', 'Content Moderation')
@section('page_title', 'Content Moderation')

@section('content')
<div class="space-y-6 max-w-7xl">

    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
        <!-- Content Type Tabs -->
        <div class="flex items-center space-x-2 text-xs">
            <a href="{{ route('admin.moderation.index', ['type' => 'reviews']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $type === 'reviews' ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Reviews
            </a>
            <a href="{{ route('admin.moderation.index', ['type' => 'topics']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $type === 'topics' ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Discussion Topics
            </a>
            <a href="{{ route('admin.moderation.index', ['type' => 'comments']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $type === 'comments' ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Comments
            </a>
        </div>

        <!-- Moderated filter checkbox -->
        <a href="{{ route('admin.moderation.index', ['type' => $type, 'only_moderated' => $onlyModerated ? 0 : 1]) }}"
            class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all flex items-center space-x-1.5 {{ $onlyModerated ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-slate-800 text-slate-400 border-slate-700' }}">
            <span>Only Moderated (is_deleted = 1)</span>
        </a>
    </div>

    <!-- Content Table -->
    <div class="bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">ID</th>
                        <th class="py-3.5 px-6">Author</th>
                        <th class="py-3.5 px-6">Context / Parent</th>
                        <th class="py-3.5 px-6">Content Preview</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($data as $item)
                        @php
                            $itemId = $item->review_id ?? $item->topic_id ?? $item->comment_id;
                            $singleType = rtrim($type, 's');
                            if($singleType === 'review') $singleType = 'review';
                            elseif($singleType === 'topic') $singleType = 'topic';
                            elseif($singleType === 'comment') $singleType = 'comment';
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-slate-500">#{{ $itemId }}</td>
                            <td class="py-4 px-6 font-semibold text-white">{{ $item->user->username ?? 'Unknown' }}</td>
                            <td class="py-4 px-6 font-mono text-indigo-400">
                                @if($type === 'reviews')
                                    {{ $item->title->title_name ?? '-' }}
                                @elseif($type === 'topics')
                                    {{ $item->title->title_name ?? '-' }} ({{ $item->category->category_name ?? '-' }})
                                @else
                                    Topic #{{ $item->topic_id }}
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-300 max-w-sm truncate">
                                @if($type === 'reviews')
                                    <span class="text-amber-400 font-bold mr-1">★ {{ $item->rating }}</span> {{ $item->review_text }}
                                @elseif($type === 'topics')
                                    <span class="font-bold text-white block">{{ $item->title }}</span>
                                    <span class="text-slate-400">{{ $item->topic_content }}</span>
                                @else
                                    {{ $item->comment_text }}
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($item->is_deleted)
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Soft-deleted
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Active
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if(!$item->is_deleted)
                                    <form action="{{ route('admin.moderation.hide') }}" method="POST" class="inline" onsubmit="return confirm('Soft-delete this {{ $singleType }}?')">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $singleType }}">
                                        <input type="hidden" name="id" value="{{ $itemId }}">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-medium">
                                            Soft Delete
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.moderation.restore') }}" method="POST" class="inline" onsubmit="return confirm('Restore this {{ $singleType }} to active?')">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $singleType }}">
                                        <input type="hidden" name="id" value="{{ $itemId }}">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-[11px] font-medium">
                                            Restore
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No content matches your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($data->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $data->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
