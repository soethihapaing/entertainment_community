@extends('layouts.admin')

@section('title', 'Discussion Categories')
@section('page_title', 'Discussion Categories')

@section('content')
<div class="space-y-6 max-w-7xl">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Category Form -->
        <div class="lg:order-2 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl h-fit">
            <!-- Header -->
            <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b border-slate-800">
                <div class="w-9 h-9 flex-shrink-0 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Add Category</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Members choose a category when they start a discussion.</p>
                </div>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" class="text-xs">
                @csrf
                <div class="px-6 py-5 space-y-5">
                    @if($errors->any())
                        <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-300">Category Name <span class="text-indigo-500">*</span></label>
                            <span class="text-[11px] text-slate-500 tabular-nums"><span id="catNameCount">0</span> / 100</span>
                        </div>
                        <input type="text" name="category_name" value="{{ old('category_name') }}" required maxlength="100" data-count-target="catNameCount" placeholder="e.g. Manga Spoilers"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">Category Type <span class="text-indigo-500">*</span></label>
                        <div class="grid grid-cols-2 gap-1 p-1 bg-slate-950 border border-slate-800 rounded-xl">
                            @foreach(['anime'   => ['Anime',   'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                      'movie'   => ['Movie',   'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z'],
                                      'book'    => ['Book',    'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                                      'general' => ['General', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z']] as $value => [$label, $icon])
                                <label class="cursor-pointer">
                                    <input type="radio" name="category_type" value="{{ $value }}" class="sr-only peer" required @checked(old('category_type', 'anime') === $value)>
                                    <span class="flex items-center justify-center gap-1.5 py-2 rounded-lg font-semibold text-slate-400 hover:text-white transition-colors peer-checked:bg-indigo-600 peer-checked:text-slate-950 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-300">Description</label>
                            <span class="text-[11px] text-slate-500 tabular-nums"><span id="catDescCount">0</span> / 1000</span>
                        </div>
                        <textarea name="description" rows="4" maxlength="1000" data-count-target="catDescCount" placeholder="What is this category for?"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs resize-none">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-800">
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-slate-950 font-bold rounded-xl shadow-md flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Create Category
                    </button>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="lg:order-1 lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Forum Categories ({{ $categories->count() }})</h3>
                    <p class="text-[11px] text-slate-400">Safe delete: a category that is still used by topics cannot be deleted.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-6">ID</th>
                            <th class="py-3 px-6">Category Name</th>
                            <th class="py-3 px-6">Type</th>
                            <th class="py-3 px-6">Topics Count</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3.5 px-6 font-mono text-slate-500">#{{ $cat->category_id }}</td>
                                <td class="py-3.5 px-6 font-bold text-white">{{ $cat->category_name }}</td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $cat->category_type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2 py-0.5 rounded-full {{ $cat->topics_count > 0 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-slate-800 text-slate-500' }} font-mono text-[11px]">
                                        {{ $cat->topics_count }} Topics
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <form action="{{ route('admin.categories.destroy', $cat->category_id) }}" method="POST" class="inline" onsubmit="return confirm('Delete category {{ $cat->category_name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs transition-all">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
