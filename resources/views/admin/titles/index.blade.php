@extends('layouts.admin')

@section('title', 'Titles & Submissions')
@section('page_title', 'Titles & Submissions')

@section('content')
<div class="space-y-6 max-w-7xl" x-data="{ openAddModal: false }">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-5 rounded-2xl border border-slate-800">
        <!-- Filter Tabs -->
        <div class="flex items-center space-x-2 overflow-x-auto text-xs">
            <a href="{{ route('admin.titles.index') }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ empty($status) && !request()->has('hidden') ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                All Titles
            </a>
            <a href="{{ route('admin.titles.index', ['status' => 'pending']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all flex items-center space-x-1.5 {{ $status === 'pending' ? 'bg-amber-600 text-white shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                <span>Pending Review</span>
                @if($pendingCount > 0)
                    <span class="px-1.5 py-0.2 bg-amber-400 text-slate-950 font-bold rounded-full text-[10px]">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.titles.index', ['status' => 'approved']) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ $status === 'approved' ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Approved
            </a>
            <a href="{{ route('admin.titles.index', ['hidden' => 1]) }}"
                class="px-3.5 py-2 rounded-xl font-medium transition-all {{ request('hidden') == 1 ? 'bg-indigo-600 text-slate-950 font-semibold shadow-md' : 'bg-slate-800 text-slate-400 hover:text-white' }}">
                Hidden
            </a>
        </div>

        <!-- Add Manual Title Button -->
        <button onclick="document.getElementById('addTitleModal').classList.remove('hidden')"
            class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-slate-950 font-bold rounded-xl text-xs shadow-md transition-all flex items-center space-x-1.5 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Manual Title</span>
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 items-start">
    <!-- Titles Table -->
    <div class="w-full lg:flex-1 min-w-0 bg-slate-900/90 border border-slate-800/80 rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider text-[11px] border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">Cover</th>
                        <th class="py-3.5 px-6">Title</th>
                        <th class="py-3.5 px-6">Media Type</th>
                        <th class="py-3.5 px-6">Genres</th>
                        <th class="py-3.5 px-6">Submission Status</th>
                        <th class="py-3.5 px-6">Visibility</th>
                        <th class="py-3.5 px-6">Submitter</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($titles as $title)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6">
                                @if($title->cover_image)
                                    <img src="{{ \Illuminate\Support\Str::startsWith($title->cover_image, ['http://', 'https://']) ? $title->cover_image : asset($title->cover_image) }}"
                                        alt="{{ $title->title_name }}"
                                        class="w-10 h-14 object-cover rounded-lg border border-slate-700">
                                @else
                                    <div class="w-10 h-14 rounded-lg border border-dashed border-slate-700 flex items-center justify-center text-[9px] text-slate-500">cover</div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-white text-sm">{{ $title->title_name }}</div>
                                @if($title->alternative_title)
                                    <div class="text-[11px] text-slate-400 italic">{{ $title->alternative_title }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded-md font-semibold text-[11px] uppercase tracking-wider bg-slate-800 text-indigo-400 border border-slate-700">
                                    {{ $title->title_type }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($title->genres as $genre)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 text-[10px]">{{ $genre->genre_name }}</span>
                                    @empty
                                        <span class="text-slate-600 italic text-[11px]">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($title->Submission_status === 'approved')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Approved
                                    </span>
                                @elseif($title->Submission_status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 inline-flex items-center gap-1.5"><span class="ping-dot"></span>
                                        Pending Review
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Rejected
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($title->is_hidden)
                                    <span class="text-amber-400 font-semibold flex items-center space-x-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        <span>Hidden</span>
                                    </span>
                                @else
                                    <span class="text-emerald-400 font-semibold flex items-center space-x-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Visible</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-400">
                                {{ $title->submitter->username ?? 'Admin' }}
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <!-- Flowchart Action A: Review Pending -->
                                @if($title->Submission_status === 'pending')
                                    <form action="{{ route('admin.titles.review', $title->title_id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 font-semibold border border-emerald-500/30">
                                            Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.titles.review', $title->title_id) }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 font-semibold border border-rose-500/30">
                                            Reject
                                        </button>
                                    </form>
                                @endif

                                <!-- Flowchart Action D & E: Hide / Restore -->
                                @if(!$title->is_hidden)
                                    <form action="{{ route('admin.titles.hide', $title->title_id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700">
                                            Hide
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.titles.restore', $title->title_id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/30">
                                            Restore
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">No titles match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($titles->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
                {{ $titles->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: ADD MANUAL TITLE (Flowchart Action B) -->
    <div id="addTitleModal" class="{{ $errors->any() ? '' : 'hidden' }} w-full lg:w-[420px] flex-shrink-0 lg:sticky lg:top-0">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full shadow-2xl relative flex flex-col lg:max-h-[calc(100vh-7.5rem)]">
            <!-- Header -->
            <div class="flex items-start justify-between px-6 pt-5 pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-bold text-white">Add Manual Title</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Manually added titles are approved automatically.</p>
                </div>
                <button type="button" onclick="document.getElementById('addTitleModal').classList.add('hidden')" title="Close"
                    class="p-1.5 -mr-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.titles.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col min-h-0 text-xs">
                @csrf
                <!-- Scrollable body -->
                <div class="px-6 py-5 space-y-5 overflow-y-auto">
                    @if($errors->any())
                        <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- 3. Cover drop zone + names -->
                    <div class="flex gap-4">
                        <div class="w-28 flex-shrink-0">
                            <label id="coverDrop" for="coverFile"
                                class="relative block w-28 h-[168px] rounded-xl border-2 border-dashed border-slate-700 bg-slate-950 hover:border-indigo-500/60 cursor-pointer overflow-hidden transition-colors">
                                <img id="coverPreview" class="absolute inset-0 w-full h-full object-cover hidden" alt="Cover preview">
                                <span id="coverPreviewText" class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-center px-2 text-slate-500">
                                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span class="text-[11px] leading-tight"><span class="text-slate-300 font-semibold">Upload cover</span><br>or drop image</span>
                                    <span class="text-[10px] text-slate-600">JPG, PNG, WEBP · 2MB</span>
                                </span>
                            </label>
                            <input type="file" id="coverFile" name="cover_image_file" accept="image/*" class="hidden">
                            <button type="button" id="coverRemove" class="hidden mt-1.5 w-full text-[11px] text-slate-400 hover:text-rose-400">Remove image</button>
                        </div>
                        <div class="flex-1 min-w-0 space-y-3">
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1.5">Title Name <span class="text-indigo-500">*</span></label>
                                <input type="text" name="title_name" value="{{ old('title_name') }}" required placeholder="e.g. Frieren: Beyond Journey's End"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1.5">Alternative Title</label>
                                <input type="text" name="alternative_title" value="{{ old('alternative_title') }}" placeholder="Sousou no Frieren"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-300 mb-1.5">or paste image URL</label>
                                <input type="url" id="coverUrl" name="cover_image" value="{{ old('cover_image') }}" placeholder="https://..."
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Media type as segmented buttons -->
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">Media Type <span class="text-indigo-500">*</span></label>
                        <div class="grid grid-cols-3 gap-1 p-1 bg-slate-950 border border-slate-800 rounded-xl">
                            @foreach(['anime' => ['Anime', 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                      'movie' => ['Movie', 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z'],
                                      'book'  => ['Book',  'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253']] as $value => [$label, $icon])
                                <label class="cursor-pointer">
                                    <input type="radio" name="title_type" value="{{ $value }}" class="sr-only peer" required @checked(old('title_type', 'anime') === $value)>
                                    <span class="flex items-center justify-center gap-1.5 py-2 rounded-lg font-semibold text-slate-400 hover:text-white transition-colors peer-checked:bg-indigo-600 peer-checked:text-slate-950 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 1. Genres as toggle chips -->
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">Genres</label>
                        <div class="flex flex-wrap gap-2">
                            @forelse($genres as $genre)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="genre_ids[]" value="{{ $genre->genre_id }}" class="sr-only peer" @checked(in_array($genre->genre_id, old('genre_ids', [])))>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-500 transition-colors peer-checked:bg-indigo-600 peer-checked:border-indigo-600 peer-checked:text-slate-950 peer-checked:font-semibold peer-checked:[&>svg]:inline peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500">
                                        <svg class="w-3 h-3 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        {{ $genre->genre_name }}
                                    </span>
                                </label>
                            @empty
                                <span class="text-slate-500">No genres yet.</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- 4. Description with character counter -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-300">Description</label>
                            <span class="text-[11px] text-slate-500 tabular-nums"><span id="descCount">0</span> / 1000</span>
                        </div>
                        <textarea id="descInput" name="description" rows="4" maxlength="1000" placeholder="Brief story overview..."
                            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-xs resize-none">{{ old('description') }}</textarea>
                    </div>
                </div>

                <!-- 4. Footer always visible -->
                <div class="px-6 py-4 border-t border-slate-800 flex justify-end gap-2 flex-shrink-0">
                    <button type="button" onclick="document.getElementById('addTitleModal').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-slate-950 rounded-xl text-xs font-bold shadow-md">
                        Save Title
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Cover image: click / drag & drop upload, live preview, remove, and URL preview
        (function () {
            const drop = document.getElementById('coverDrop');
            const file = document.getElementById('coverFile');
            const url = document.getElementById('coverUrl');
            const img = document.getElementById('coverPreview');
            const placeholder = document.getElementById('coverPreviewText');
            const remove = document.getElementById('coverRemove');

            function show(src) {
                if (src) { img.src = src; img.classList.remove('hidden'); placeholder.classList.add('hidden'); }
                else { img.removeAttribute('src'); img.classList.add('hidden'); placeholder.classList.remove('hidden'); }
            }
            function refresh() {
                const f = file.files && file.files[0];
                remove.classList.toggle('hidden', !f);
                if (f) show(window.URL.createObjectURL(f));
                else show(/^https?:\/\//.test(url.value.trim()) ? url.value.trim() : '');
            }

            file.addEventListener('change', refresh);
            url.addEventListener('input', refresh);
            remove.addEventListener('click', function () { file.value = ''; refresh(); });
            img.addEventListener('error', function () { if (!(file.files && file.files[0])) show(''); });

            ['dragenter', 'dragover'].forEach(function (ev) {
                drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('border-indigo-500'); });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('border-indigo-500'); });
            });
            drop.addEventListener('drop', function (e) {
                if (e.dataTransfer.files.length && e.dataTransfer.files[0].type.startsWith('image/')) {
                    file.files = e.dataTransfer.files;
                    refresh();
                }
            });
            refresh();

            // Description character counter
            const desc = document.getElementById('descInput');
            const count = document.getElementById('descCount');
            function updateCount() { count.textContent = desc.value.length; }
            desc.addEventListener('input', updateCount);
            updateCount();
        })();
    </script>
    </div>

</div>
@endsection
