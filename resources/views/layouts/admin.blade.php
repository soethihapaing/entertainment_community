<!DOCTYPE html>
<html lang="my" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') — Community Platform</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Brand palette: Midnight Navy #0B132B (60%), Dark Blue #1C2541 (30%), Orange Sunset #FF6B35 (10%)
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            50: '#F4F6FB', 100: '#E6EAF3', 200: '#C9D1E3', 300: '#A9B4CE', 400: '#8794B5',
                            500: '#66739A', 600: '#4A5680', 700: '#34406A', 800: '#263156',
                            900: '#1C2541', // Dark Blue — cards, sidebar, navbar, inputs
                            950: '#0B132B', // Midnight Navy — main background
                        },
                        indigo: {
                            50: '#FFF3EE', 100: '#FFE4D8', 200: '#FFC6AE', 300: '#FFA47F', 400: '#FF8757',
                            500: '#FF7A47',
                            600: '#FF6B35', // Orange Sunset — accent
                            700: '#E5531F', 800: '#BF4419', 900: '#8F3313', 950: '#4D1B0A',
                        },
                        violet: {
                            50: '#FFF3EE', 100: '#FFE4D8', 200: '#FFC6AE', 300: '#FFA47F', 400: '#FF8757',
                            500: '#FF6B35', 600: '#F25A22', 700: '#D94A15', 800: '#B33D12', 900: '#8F3313', 950: '#4D1B0A',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Noto Sans Myanmar', 'Pyidaungsu', sans-serif; }

        /* SIDEBAR: lighter icon rail (left, 4rem) + rounded label panel (right, 12rem) */
        #sidebar { position: relative; width: 16rem; background: #263156; overflow: hidden; transition: width .2s ease; }
        #sidebar.sidebar-collapsed { width: 4rem; }                     /* collapsed = icon rail only */
        #sidebar::before {                                               /* label panel background */
            content: ''; position: absolute; top: 0; bottom: 0; left: 4rem; width: 12rem;
            background: #1C2541; border-top-left-radius: 2rem;
        }
        #sidebar > * { position: relative; }                             /* keep content above the panel */

        #sidebar .nav-link { height: 2.75rem; }
        #sidebar .nav-link svg { flex-shrink: 0; margin: 0 1.375rem; }   /* centres the icon in the 4rem rail */
        #sidebar .nav-link > span {
            position: relative; flex: 1; height: 100%; display: flex; align-items: center;
            margin-left: .75rem; padding-left: 1rem; white-space: nowrap;
            border-radius: 1.375rem 0 0 1.375rem; transition: background-color .15s ease, color .15s ease;
        }
        #sidebar .nav-link:hover > span { background: rgba(11, 19, 43, .35); }

        /* Active page: tab in the page background colour, with curved corners joining the content */
        #sidebar .nav-link.is-active svg { color: #FF6B35; }
        #sidebar .nav-link.is-active > span { background: #0B132B; color: #FF6B35; font-weight: 600; }
        #sidebar .nav-link.is-active > span::before,
        #sidebar .nav-link.is-active > span::after {
            content: ''; position: absolute; right: 0; width: 1.25rem; height: 1.25rem; pointer-events: none;
        }
        #sidebar .nav-link.is-active > span::before { top: -1.25rem; background: radial-gradient(circle at top left, transparent 1.25rem, #0B132B 1.3rem); }
        #sidebar .nav-link.is-active > span::after { bottom: -1.25rem; background: radial-gradient(circle at bottom left, transparent 1.25rem, #0B132B 1.3rem); }

        /* ================= ANIMATIONS (short, small, all ease-out) ================= */
        @keyframes fadeUp      { from { opacity: 0; transform: translateY(6px); }  to { opacity: 1; transform: none; } }
        @keyframes slideInRight{ from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: none; } }
        @keyframes slideDown   { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
        @keyframes softPing    { 0% { transform: scale(1); opacity: .7; } 80%, 100% { transform: scale(2.4); opacity: 0; } }

        /* 1. Page content appears one block after another ("backwards" so hover effects still work afterwards) */
        main > div > *,
        main > div > .grid > * { animation: fadeUp .3s ease-out backwards; }
        main > div > *:nth-child(2), main > div > .grid > *:nth-child(2) { animation-delay: .05s; }
        main > div > *:nth-child(3), main > div > .grid > *:nth-child(3) { animation-delay: .1s; }
        main > div > *:nth-child(4), main > div > .grid > *:nth-child(4) { animation-delay: .15s; }
        main > div > *:nth-child(n+5), main > div > .grid > *:nth-child(n+5) { animation-delay: .2s; }

        /* 3. Add Title panel slides in from the right */
        #addTitleModal:not(.hidden) { animation: slideInRight .25s ease-out; }

        /* 4. Flash messages slide down, then fade away */
        .flash-message { animation: slideDown .25s ease-out; transition: opacity .3s ease-out, transform .3s ease-out; }
        .flash-message.is-leaving { opacity: 0; transform: translateY(-6px); }

        /* 5. Cards lift slightly on hover */
        .hover-lift { transition: transform .2s ease-out, box-shadow .2s ease-out, border-color .2s ease-out, background-color .2s ease-out; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 14px 28px -14px rgba(255, 107, 53, .35); }

        /* 6. Sidebar: smoother open/close, labels fade, icons nudge on hover */
        #sidebar { transition: width .25s cubic-bezier(.4, 0, .2, 1); }
        #sidebar .nav-link > span { transition: background-color .15s ease, color .15s ease, opacity .2s ease-out; }
        #sidebar.sidebar-collapsed .nav-link > span { opacity: 0; }
        #sidebar .nav-link svg { transition: transform .15s ease-out, color .15s ease-out; }
        #sidebar .nav-link:hover svg { transform: translateX(2px); }

        /* TABLES: rows fade in one by one, and highlight on hover (empty-state rows are skipped) */
        main table tbody tr { animation: fadeUp .3s ease-out backwards; transition: background-color .15s ease-out; }
        main table tbody tr:nth-child(2)  { animation-delay: .03s; }
        main table tbody tr:nth-child(3)  { animation-delay: .06s; }
        main table tbody tr:nth-child(4)  { animation-delay: .09s; }
        main table tbody tr:nth-child(5)  { animation-delay: .12s; }
        main table tbody tr:nth-child(6)  { animation-delay: .15s; }
        main table tbody tr:nth-child(7)  { animation-delay: .18s; }
        main table tbody tr:nth-child(n+8) { animation-delay: .21s; }
        main table tbody tr:not(:has(td[colspan])):hover { background-color: rgba(38, 49, 86, .55); }
        main table tbody tr > td:first-child { transition: box-shadow .15s ease-out; }
        main table tbody tr:not(:has(td[colspan])):hover > td:first-child { box-shadow: inset 3px 0 0 #FF6B35; }   /* orange bar on the left */
        main table tbody tr:not(:has(td[colspan])):hover > td { color: #E6EAF3; }
        main table tbody td img { transition: transform .2s ease-out, box-shadow .2s ease-out; }
        main table tbody tr:hover td img { transform: scale(1.08); box-shadow: 0 6px 14px -6px rgba(0, 0, 0, .6); }   /* cover thumbnails pop */
        main table tbody td form button { transition: transform .1s ease-out, background-color .15s ease, color .15s ease, border-color .15s ease, opacity .15s ease; }
        main table tbody tr td form button { opacity: .75; }
        main table tbody tr:hover td form button { opacity: 1; }   /* action buttons light up on the hovered row */

        /* 7. Buttons shrink a little when pressed */
        button { transition: transform .1s ease-out, background-color .15s ease, color .15s ease, border-color .15s ease; }
        button:not(:disabled):active { transform: scale(.96); }

        /* 8. Pending badge: only a small dot pulses, not the whole badge */
        .ping-dot { position: relative; display: inline-block; width: .4rem; height: .4rem; border-radius: 9999px; background: currentColor; }
        .ping-dot::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: currentColor; animation: softPing 1.8s ease-out infinite; }

        /* MOUSE EFFECTS (only on devices with a real mouse) */
        @media (hover: hover) and (pointer: fine) {
            /* 1. Spotlight: soft orange light follows the cursor inside cards, and the border lights up near it */
            .spotlight { position: relative; }
            .spotlight:hover {
                background-image: radial-gradient(320px circle at var(--mx, 50%) var(--my, 50%), rgba(255, 107, 53, .16), rgba(255, 107, 53, .05) 40%, transparent 70%);
            }
            .spotlight::after {
                content: ''; position: absolute; inset: -1px; border-radius: inherit; padding: 1.5px; pointer-events: none;
                background: radial-gradient(260px circle at var(--mx, 50%) var(--my, 50%), rgba(255, 107, 53, 1), rgba(255, 107, 53, .35) 40%, transparent 70%);
                -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
                -webkit-mask-composite: xor; mask-composite: exclude;
                opacity: 0; transition: opacity .25s ease-out;
            }
            .spotlight:hover::after { opacity: 1; }

            /* 2. Ambient glow: a very faint light on the page background that drifts after the cursor */
            #contentArea {
                background-image: radial-gradient(650px circle at var(--gx, 70%) var(--gy, 20%), rgba(255, 107, 53, .13), rgba(255, 107, 53, .04) 40%, transparent 70%);
            }
        }

        /* Respect the "reduce motion" system setting */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body class="h-full bg-slate-950 flex overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="sidebar-collapsed flex flex-col flex-shrink-0">
        <script>
            // Restore the sidebar state the admin chose last time
            try { if (localStorage.getItem('sidebarOpen') === '1') document.getElementById('sidebar').classList.remove('sidebar-collapsed'); } catch (e) {}
            function toggleSidebar() {
                const open = !document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('sidebarOpen', open ? '1' : '0'); } catch (e) {}
            }
        </script>
        <!-- Logo -->
        <div class="h-16 flex items-center flex-shrink-0">
            <!-- Rail: open / close button -->
            <button type="button" onclick="toggleSidebar()" title="Menu" class="w-16 h-16 flex-shrink-0 flex items-center justify-center text-slate-300 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <!-- Panel: logo -->
            <div class="flex items-center space-x-2.5 pl-5 whitespace-nowrap">
                <x-pov-mark class="w-9 h-9" />
                <div>
                    <span class="font-bold text-white text-sm tracking-tight block leading-tight">Point of View</span>
                    <span class="text-[10px] text-indigo-400 font-semibold block uppercase tracking-wider">Admin Panel</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links (Module 01 - 08) -->
        <nav class="flex-1 py-4 space-y-1 overflow-y-auto overflow-x-hidden">

            <!-- Module 01: Dashboard -->
            <a href="{{ route('admin.dashboard') }}" title="Dashboard"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Module 02: Users -->
            <a href="{{ route('admin.users.index') }}" title="User Management"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>User Management</span>
            </a>

            <!-- Module 03: Titles -->
            <a href="{{ route('admin.titles.index') }}" title="Titles &amp; Submissions"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.titles.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                </svg>
                <span>Titles & Submissions</span>
            </a>

            <!-- Module 04: Genres -->
            <a href="{{ route('admin.genres.index') }}" title="Genres"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.genres.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span>Genres</span>
            </a>

            <!-- Module 05: Categories -->
            <a href="{{ route('admin.categories.index') }}" title="Discussion Categories"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.categories.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Discussion Categories</span>
            </a>

            <!-- Module 06: Reports -->
            <a href="{{ route('admin.reports.index') }}" title="Report Management"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.reports.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Report Management</span>
            </a>

            <!-- Module 07: Moderation -->
            <a href="{{ route('admin.moderation.index') }}" title="Content Moderation"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.moderation.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Content Moderation</span>
            </a>

            <!-- Module 08: Logs -->
            <a href="{{ route('admin.logs.index') }}" title="Activity Logs"
                class="nav-link flex items-center text-sm font-medium transition-all {{ request()->routeIs('admin.logs.*') ? 'is-active' : 'text-slate-400 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Activity Logs</span>
            </a>
        </nav>

        <!-- Current Admin Footer Info -->
        <div class="h-16 flex items-center flex-shrink-0">
            <!-- Rail: logout -->
            <form action="{{ route('logout') }}" method="POST" class="w-16 flex-shrink-0 flex justify-center">
                @csrf
                <button type="submit" title="Logout" class="p-2 rounded-lg text-slate-300 hover:text-rose-400 hover:bg-rose-500/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
            <!-- Panel: current admin -->
            <div class="flex items-center space-x-2.5 pl-5 pr-3 min-w-0 whitespace-nowrap">
                <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 font-bold text-sm flex items-center justify-center border border-indigo-500/30 flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->username ?? 'A', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->username ?? 'Admin' }}</p>
                    <span class="text-[11px] text-emerald-400 font-medium flex items-center space-x-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                        <span>Active Admin</span>
                    </span>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div id="contentArea" class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-950">

        <!-- Top Header -->
        <header class="h-16 px-8 flex items-center justify-between flex-shrink-0">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight leading-tight">@yield('page_title', 'Admin Overview')</h2>
                @hasSection('page_subtitle')
                    <p class="text-xs text-slate-400 mt-0.5">@yield('page_subtitle')</p>
                @endif
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2.5 bg-slate-900 border border-slate-800 px-3.5 py-1.5 rounded-full text-xs">
                    <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="headerClockTime" class="font-bold text-white tabular-nums">{{ now()->format('g:i A') }}</span>
                    <span class="w-px h-3.5 bg-slate-700"></span>
                    <span id="headerClockDate" class="text-slate-400">{{ now()->format('D j M Y') }}</span>
                </div>
                <script>
                    // Live clock in the admin's local time, updated every second
                    (function () {
                        const timeEl = document.getElementById('headerClockTime');
                        const dateEl = document.getElementById('headerClockDate');
                        function tick() {
                            const d = new Date();
                            timeEl.textContent = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                            dateEl.textContent = d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                        }
                        tick();
                        setInterval(tick, 1000);
                    })();
                </script>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-8 pt-4">
            @if(session('success'))
                <div class="flash-message p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center space-x-3 mb-2" data-dismiss-after="5000">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="flash-message p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center space-x-3 mb-2" data-dismiss-after="8000">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>

        <!-- Scrollable Page Content -->
        <main class="flex-1 overflow-y-auto px-8 py-6">
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // 2. Count-up numbers: <h3 data-count-up>1,234</h3> counts from 0 to 1,234
            document.querySelectorAll('[data-count-up]').forEach(function (el) {
                const target = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10);
                if (!target || reduceMotion) return;
                const duration = 800;
                const start = performance.now();
                el.textContent = '0';
                function step(now) {
                    const p = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - p, 3);              // ease-out
                    el.textContent = Math.round(target * eased).toLocaleString('en-US');
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });

            // Character counters: <textarea data-count-target="someId"> updates <span id="someId">
            document.querySelectorAll('[data-count-target]').forEach(function (input) {
                const out = document.getElementById(input.dataset.countTarget);
                if (!out) return;
                function update() { out.textContent = input.value.length; }
                input.addEventListener('input', update);
                update();
            });

            // MOUSE EFFECTS — only with a real mouse
            if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                // 1. Spotlight on cards: every rounded card / panel in the page, plus quick-nav buttons
                document.querySelectorAll('main .rounded-2xl, main .hover-lift').forEach(function (card) {
                    card.classList.add('spotlight');
                    card.addEventListener('pointermove', function (e) {
                        const r = card.getBoundingClientRect();
                        card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                        card.style.setProperty('--my', (e.clientY - r.top) + 'px');
                    });
                });

                // 2. Ambient glow that drifts smoothly after the cursor (skipped when "reduce motion" is on)
                const area = document.getElementById('contentArea');
                if (area && !reduceMotion) {
                    let tx = 0, ty = 0, x = 0, y = 0, running = false;
                    function frame() {
                        x += (tx - x) * 0.08;
                        y += (ty - y) * 0.08;
                        area.style.setProperty('--gx', x + 'px');
                        area.style.setProperty('--gy', y + 'px');
                        if (Math.abs(tx - x) > 0.5 || Math.abs(ty - y) > 0.5) requestAnimationFrame(frame);
                        else running = false;
                    }
                    area.addEventListener('pointermove', function (e) {
                        const r = area.getBoundingClientRect();
                        tx = e.clientX - r.left;
                        ty = e.clientY - r.top;
                        if (!running) { running = true; requestAnimationFrame(frame); }
                    });
                    const r0 = area.getBoundingClientRect();
                    x = tx = r0.width * 0.7; y = ty = r0.height * 0.2;
                    area.style.setProperty('--gx', x + 'px');
                    area.style.setProperty('--gy', y + 'px');
                }
            }

            // 4. Flash messages fade away on their own
            document.querySelectorAll('.flash-message[data-dismiss-after]').forEach(function (el) {
                setTimeout(function () {
                    el.classList.add('is-leaving');
                    setTimeout(function () { el.remove(); }, 300);
                }, parseInt(el.dataset.dismissAfter, 10));
            });
        })();
    </script>
</body>
</html>
