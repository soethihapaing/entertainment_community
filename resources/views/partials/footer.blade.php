{{-- Site footer: brand, link columns, social icons, copyright. --}}
@php
    $footerColumns = [
        'Explore' => [
            ['label' => 'Movies', 'href' => route('home') . '#categories'],
            ['label' => 'Series', 'href' => route('home') . '#categories'],
            ['label' => 'Anime', 'href' => route('home') . '#categories'],
            ['label' => 'Manga', 'href' => route('home') . '#categories'],
            ['label' => 'Books', 'href' => route('home') . '#categories'],
        ],
        'Community' => [
            ['label' => 'Feed', 'href' => route('feed')],
            ['label' => 'Trending', 'href' => route('home') . '#trending'],
            ['label' => 'Join us', 'href' => route('register')],
            ['label' => 'Log in', 'href' => route('login')],
        ],
        'About' => [
            ['label' => 'How it works', 'href' => route('home') . '#about'],
            ['label' => 'Guidelines', 'href' => '#'],
            ['label' => 'Privacy', 'href' => '#'],
            ['label' => 'Contact', 'href' => '#'],
        ],
    ];

    $socials = [
        ['icon' => 'bi-instagram', 'label' => 'Instagram'],
        ['icon' => 'bi-tiktok', 'label' => 'TikTok'],
        ['icon' => 'bi-discord', 'label' => 'Discord'],
        ['icon' => 'bi-youtube', 'label' => 'YouTube'],
        ['icon' => 'bi-twitter-x', 'label' => 'X'],
    ];
@endphp

<footer class="pov-footer">
    <div class="container position-relative">
        <div class="row gx-4 gy-5">
            <div class="col-lg-4" data-reveal>
                <a class="pov-brand mb-3" href="{{ route('home') }}">
                    <span class="pov-brand__icon" aria-hidden="true"><i class="bi bi-eye-fill"></i></span>
                    <span>Point <em>of</em> View</span>
                </a>
                <p class="mb-4" style="max-width: 22rem">
                    A cozy corner of the internet for people who watch, read and can't stop talking about it.
                </p>
                <div class="social-links">
                    @foreach ($socials as $social)
                        <a href="#" class="social-link" aria-label="Point of View on {{ $social['label'] }}">
                            <i class="bi {{ $social['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            @foreach ($footerColumns as $heading => $links)
                <div class="col-6 col-md-4 col-lg-2 {{ $loop->first ? 'offset-lg-2' : '' }}" data-reveal style="--reveal-delay: {{ $loop->iteration * 0.1 }}s">
                    <h2 class="footer-heading">{{ $heading }}</h2>
                    <ul class="footer-links">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>&copy; {{ now()->year }} Point of View. All rights reserved.</span>
            <span>Made with <i class="bi bi-heart-fill" style="color: var(--pov-pink)" aria-label="love"></i> for stories &amp; the people who love them.</span>
        </div>
    </div>
</footer>
