{{--
    Main navigation.
    - Transparent at the top, glass after scrolling (main.js adds .is-scrolled).
    - Below the lg breakpoint the links live in a Bootstrap offcanvas that slides in from the right.
    - On the homepage, section links are highlighted while scrolling (scroll-spy in main.js).
--}}
@php
    $isHome = request()->routeIs('home');
    $homeUrl = route('home');

    $navLinks = [
        ['label' => 'Home', 'href' => $homeUrl . '#top', 'section' => 'top', 'active' => $isHome],
        ['label' => 'Explore', 'href' => $homeUrl . '#trending', 'section' => 'trending', 'active' => false],
        ['label' => 'Categories', 'href' => $homeUrl . '#categories', 'section' => 'categories', 'active' => false],
        ['label' => 'Community', 'href' => route('feed'), 'section' => null, 'active' => request()->routeIs('feed')],
        ['label' => 'About', 'href' => $homeUrl . '#about', 'section' => 'about', 'active' => false],
    ];

    // TODO (Backend): replace this with @auth / @guest once Laravel auth is connected.
    $isSignedIn = request()->routeIs('feed');
@endphp

<header>
    <nav class="navbar navbar-expand-lg fixed-top pov-navbar" id="mainNav" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand pov-brand" href="{{ route('home') }}" aria-label="Point of View — home">
                <span class="pov-brand__icon" aria-hidden="true"><i class="bi bi-eye-fill"></i></span>
                <span>Point <em>of</em> View</span>
            </a>

            <button class="pov-burger d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#navOffcanvas" aria-controls="navOffcanvas" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>

            <div class="offcanvas offcanvas-end pov-offcanvas" tabindex="-1" id="navOffcanvas" aria-labelledby="navOffcanvasLabel">
                <div class="offcanvas-header">
                    <span class="pov-brand" id="navOffcanvasLabel">
                        <span class="pov-brand__icon" aria-hidden="true"><i class="bi bi-eye-fill"></i></span>
                        <span>Point <em>of</em> View</span>
                    </span>
                    <button type="button" class="btn-close-pov" data-bs-dismiss="offcanvas" aria-label="Close menu">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="offcanvas-body align-items-lg-center">
                    <ul class="navbar-nav mx-lg-auto">
                        @foreach ($navLinks as $link)
                            <li class="nav-item">
                                <a
                                    class="nav-link pov-nav-link {{ $link['active'] ? 'active' : '' }}"
                                    href="{{ $link['href'] }}"
                                    @if ($isHome && $link['section']) data-section="{{ $link['section'] }}" @endif
                                    @if ($link['active']) aria-current="page" @endif
                                >{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="pov-nav-actions">
                        @if ($isSignedIn)
                            <button type="button" class="nav-icon-btn d-none d-lg-grid" aria-label="Search" data-toast="Search is coming soon" data-toast-title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                            <button type="button" class="nav-icon-btn d-none d-lg-grid" aria-label="Notifications (3 new)" data-toast="You have 3 new notifications" data-toast-title="Notifications">
                                <i class="bi bi-bell"></i>
                                <span class="nav-icon-btn__dot"></span>
                            </button>
                            <div class="dropdown">
                                <button class="btn-pov btn-pov-ghost btn-pov-sm w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="pov-avatar pov-avatar--sm" style="--size: 26px; --avatar-hue: 22" aria-hidden="true">HK</span>
                                    Hana <i class="bi bi-chevron-down small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-pov">
                                    <li><a class="dropdown-item" href="{{ route('feed') }}"><i class="bi bi-person"></i> My profile</a></li>
                                    <li><a class="dropdown-item" href="{{ route('feed') }}"><i class="bi bi-bookmark"></i> Bookmarks</a></li>
                                    <li><a class="dropdown-item" href="{{ route('feed') }}"><i class="bi bi-gear"></i> Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    {{-- TODO (Backend): turn this into a POST form to route('logout') with @csrf --}}
                                    <li><a class="dropdown-item" href="{{ route('home') }}" data-logout><i class="bi bi-box-arrow-right"></i> Log out</a></li>
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn-pov btn-pov-ghost btn-pov-sm">Log in</a>
                            <a href="{{ route('register') }}" class="btn-pov btn-pov-primary btn-pov-sm">
                                Sign up <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
