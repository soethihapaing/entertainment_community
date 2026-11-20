<!DOCTYPE html>
{{--
    Shared layout for every page.

    Sections a page can define:
      @section('title')       → browser tab title
      @section('body_class')  → extra classes on <body> (e.g. "page-home")
      @section('hide_chrome') → hides navbar + footer (used by the login page)
    Stacks:
      @push('styles')  → page CSS files
      @push('scripts') → page JS files
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Point of View — a community to share reviews and opinions about movies, series, anime, manga and books.">
    <meta name="theme-color" content="#070B1F">

    <title>@hasSection('title')@yield('title') · @endif Point of View</title>

    {{-- Swap "no-js" → "js" immediately so load-in animations only hide content when JS can reveal it --}}
    <script>document.documentElement.classList.replace('no-js', 'js');</script>
    @stack('head')

    {{-- Google Fonts: Bebas Neue (display) + Poppins (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@300;400;500;600;700&display=swap">

    {{-- Bootstrap 5 + Bootstrap Icons (CDN) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Theme CSS (order matters: tokens → base → components → animations → page) --}}
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animations.css') }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">
    <a href="#main-content" class="skip-link">Skip to content</a>

    @include('partials.preloader')

    {{-- Decorative layers --}}
    <div class="film-grain" aria-hidden="true"></div>
    <div class="cursor-dot" aria-hidden="true"></div>
    <div class="cursor-ring" aria-hidden="true"></div>

    @unless (View::hasSection('hide_chrome'))
        @include('partials.navbar')
    @endunless

    <main id="main-content" tabindex="-1">
        @yield('content')
    </main>

    @unless (View::hasSection('hide_chrome'))
        @include('partials.footer')
    @endunless

    {{-- Toasts are injected here by POV.toast() in main.js --}}
    <div class="pov-toast-stack" id="toastStack" aria-live="polite" aria-atomic="false"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/animations.js') }}"></script>
    @stack('scripts')
</body>
</html>
