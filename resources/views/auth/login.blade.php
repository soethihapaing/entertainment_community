<!DOCTYPE html>
<html lang="my" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Entertainment Community</title>
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
                            900: '#1C2541', // Dark Blue
                            950: '#0B132B', // Midnight Navy
                        },
                        indigo: {
                            50: '#FFF3EE', 100: '#FFE4D8', 200: '#FFC6AE', 300: '#FFA47F', 400: '#FF8757',
                            500: '#FF7A47',
                            600: '#FF6B35', // Orange Sunset
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
    </style>
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 p-4">
    <div class="w-full max-w-md">
        <!-- Brand Logo / Header -->
        <div class="text-center mb-8">
            <!-- POV wordmark: P + lens mark + V -->
            <div class="inline-flex items-center gap-1 mb-4 text-[#F6F2EA] font-extrabold leading-none select-none" style="font-size: 3.5rem; letter-spacing: -.02em;" aria-label="POV">
                <span aria-hidden="true">P</span>
                <x-pov-mark class="w-[3.4rem] h-[3.4rem]" />
                <span aria-hidden="true">V</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Community Admin Portal</h1>
            <p class="text-sm text-slate-400 mt-1">Anime, Movie & Book Platform Management</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-black/50">
            <!-- Alert / Error message -->
            @if (isset($errors) && $errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-start space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Username / Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input id="loginInput" type="text" name="login" value="{{ old('login', 'admin') }}" required autofocus
                            class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="admin or admin@community.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input id="passwordInput" type="password" name="password" value="admin123" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 via-indigo-500 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 text-sm">
                    Sign in to Admin Portal
                </button>
            </form>

            <!-- Demo Account Pill -->
            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <span class="text-xs text-slate-500">Test admin account:</span>
                <div class="mt-2 inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700/60 text-xs text-slate-300 font-mono">
                    <span class="text-indigo-400 font-semibold">admin</span>
                    <span class="text-slate-600">|</span>
                    <span class="text-emerald-400 font-semibold">admin123</span>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-600 mt-6">
            © 2026 Entertainment Community Platform. All rights reserved.
        </p>
    </div>
</body>
</html>
