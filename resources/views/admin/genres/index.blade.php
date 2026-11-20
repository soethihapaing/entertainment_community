@extends('layouts.admin')

@section('title', 'Genre Management')
@section('page_title', 'Genre Management')

@section('content')
<div class="space-y-6 max-w-7xl">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Add Genre Form -->
        <div class="lg:order-2 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl h-fit">
            <!-- Header -->
            <div class="flex items-start gap-3 px-6 pt-5 pb-4 border-b border-slate-800">
                <div class="w-9 h-9 flex-shrink-0 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Add New Genre</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Genres can be assigned to any anime, movie or book.</p>
                </div>
            </div>

            <form action="{{ route('admin.genres.store') }}" method="POST" class="text-xs">
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
                            <label class="font-semibold text-slate-300">Genre Name <span class="text-indigo-500">*</span></label>
                            <span class="text-[11px] text-slate-500 tabular-nums"><span id="genreNameCount">0</span> / 100</span>
                        </div>
                        <input type="text" id="genreNameInput" name="genre_name" value="{{ old('genre_name') }}" required maxlength="100" data-count-target="genreNameCount" placeholder="e.g. Psychological, Cyberpunk"
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                    </div>

                    <!-- Live preview of the genre chip as it will appear on the Add Title form -->
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-slate-950/60 border border-dashed border-slate-800">
                        <span class="text-[11px] text-slate-500">Preview</span>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-indigo-600 text-slate-950 font-semibold">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span id="genrePreview">Genre name</span>
                        </span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-300">Description</label>
                            <span class="text-[11px] text-slate-500 tabular-nums"><span id="genreDescCount">0</span> / 1000</span>
                        </div>
                        <textarea name="description" rows="4" maxlength="1000" data-count-target="genreDescCount" placeholder="Brief genre description..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs resize-none">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-800">
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-slate-950 font-bold rounded-xl shadow-md flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Create Genre
                    </button>
                </div>
            </form>
            <script>
                (function () {
                    const input = document.getElementById('genreNameInput');
                    const preview = document.getElementById('genrePreview');
                    function update() { preview.textContent = input.value.trim() || 'Genre name'; }
                    input.addEventListener('input', update);
                    update();
                })();
            </script>
        </div>

        <!-- Genres List -->
        <div class="lg:order-1 lg:col-span-2 bg-slate-900/90 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Master Genres ({{ $genres->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-6">ID</th>
                            <th class="py-3 px-6">Genre Name</th>
                            <th class="py-3 px-6">Description</th>
                            <th class="py-3 px-6">Titles Linked</th>
                            <th class="py-3 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @forelse($genres as $genre)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="py-3.5 px-6 font-mono text-slate-500">#{{ $genre->genre_id }}</td>
                                <td class="py-3.5 px-6 font-bold text-white">{{ $genre->genre_name }}</td>
                                <td class="py-3.5 px-6 text-slate-400 max-w-xs truncate">{{ $genre->description ?? '-' }}</td>
                                <td class="py-3.5 px-6">
                                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-indigo-400 font-mono text-[11px]">
                                        {{ $genre->titles_count }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-6 text-right">
                                    <form action="{{ route('admin.genres.destroy', $genre->genre_id) }}" method="POST" class="inline" onsubmit="return confirm('Delete genre {{ $genre->genre_name }}?')">
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
                                <td colspan="5" class="py-8 text-center text-slate-500">No genres yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
