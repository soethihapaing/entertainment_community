@extends('layouts.app')

@section('title', 'Share your Point of View')
@section('body_class', 'page-home')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
@endpush

@section('content')

    {{-- =====================================================================
         HERO — animated poster wall + headline + CTAs
         ===================================================================== --}}
    <section class="hero" id="top" aria-labelledby="heroTitle">
        <x-poster-background />

        {{-- Floating decorative elements --}}
        <div class="hero__decor" aria-hidden="true">
            <span class="orb orb--orange hero__orb hero__orb--1 float-slow"></span>
            <span class="orb orb--pink hero__orb hero__orb--2 float-medium"></span>
            <i class="bi bi-film hero__float hero__float--1 float-slow"></i>
            <i class="bi bi-camera-reels hero__float hero__float--2 float-medium"></i>
            <i class="bi bi-book-half hero__float hero__float--3 float-fast"></i>
            <span class="sparkle" style="top: 22%; left: 14%; --d: 0s"></span>
            <span class="sparkle" style="top: 30%; right: 16%; --d: 1.1s"></span>
            <span class="sparkle sparkle--sm" style="bottom: 26%; left: 22%; --d: 2s"></span>
            <span class="sparkle sparkle--sm" style="bottom: 32%; right: 24%; --d: 0.6s"></span>
        </div>

        <div class="container hero__content text-center">
            <span class="section-eyebrow hero__eyebrow" data-load style="--delay: 0.05s">
                <span class="live-dot"></span> 12,500+ storytellers online
            </span>

            <h1 class="hero__title" id="heroTitle" data-load style="--delay: 0.15s">
                <span class="d-block">See it. Feel it.</span>
                <span class="d-block hero__title-shimmer">Share your Point of View.</span>
            </h1>

            <p class="hero__rotator" data-load style="--delay: 0.3s">
                Talk about
                <span class="typing" data-rotate-words='["Movies", "Series", "Anime", "Manga", "Books"]'>
                    <span data-typing-text aria-hidden="true">Movies</span><span class="typing__caret" aria-hidden="true"></span>
                    <span class="visually-hidden">movies, series, anime, manga and books</span>
                </span>
            </p>

            <p class="hero__subtitle" data-load style="--delay: 0.42s">
                Drop honest reviews, hot takes and fan theories, then discuss them with people who
                watched and read the same stories you love.
            </p>

            <div class="hero__actions" data-load style="--delay: 0.55s">
                <a href="{{ route('register') }}" class="btn-pov btn-pov-primary btn-pov-lg">
                    Join the Community <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#trending" class="btn-pov btn-pov-outline btn-pov-lg">
                    <i class="bi bi-compass"></i> Explore Discussions
                </a>
            </div>
        </div>

        <a href="#categories" class="hero__scroll" aria-label="Scroll to categories"><span></span></a>
    </section>

    {{-- =====================================================================
         CATEGORIES
         ===================================================================== --}}
    <section class="section categories" id="categories" aria-labelledby="categoriesTitle">
        <span class="orb orb--blue" style="top: 10%; right: -10%; width: 420px; height: 420px; opacity: .35" aria-hidden="true"></span>

        <div class="container position-relative">
            <div class="text-center mb-5">
                <span class="section-eyebrow" data-reveal><i class="bi bi-grid"></i> Pick your universe</span>
                <h2 class="section-title" id="categoriesTitle" data-reveal style="--reveal-delay: .1s">
                    Five worlds. <span class="text-sunset">One community.</span>
                </h2>
                <p class="section-lead mx-auto" data-reveal style="--reveal-delay: .2s">
                    Whatever you're watching or reading tonight, there's a crowd ready to talk about it.
                </p>
            </div>

            <div class="row g-3 g-lg-4 row-cols-2 row-cols-md-3 row-cols-lg-5 justify-content-center">
                @foreach ($categories as $category)
                    <div class="col" data-reveal style="--reveal-delay: {{ $loop->index * 0.08 }}s">
                        <x-category-card :category="$category" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =====================================================================
         TRENDING DISCUSSIONS
         TODO (Backend): $trendingPosts will come from the posts table.
         ===================================================================== --}}
    <section class="section trending pt-0 pb-0" id="trending" aria-labelledby="trendingTitle">
        <div class="container">
            <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-4 mb-5">
                <div>
                    <span class="section-eyebrow" data-reveal><i class="bi bi-fire"></i> Hot right now</span>
                    <h2 class="section-title mb-0" id="trendingTitle" data-reveal style="--reveal-delay: .1s">
                        Trending <span class="text-sunset">Discussions</span>
                    </h2>
                </div>

                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter discussions by category"
                     data-filter-group="#trendingGrid" data-reveal style="--reveal-delay: .2s">
                    <button type="button" class="pov-chip is-active" data-filter="all" aria-pressed="true">
                        <i class="bi bi-grid-3x3-gap"></i> All
                    </button>
                    @foreach ($categories as $category)
                        <button type="button" class="pov-chip" data-filter="{{ $category['slug'] }}" aria-pressed="false">
                            <i class="bi {{ $category['icon'] }}"></i> {{ $category['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="row g-4" id="trendingGrid">
                @foreach ($trendingPosts as $post)
                    <div class="col-md-6 col-lg-4" data-filter-item="{{ $post['category'] }}" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 0.1 }}s">
                        <x-post-card :post="$post" />
                    </div>
                @endforeach

                <div class="col-12" data-filter-empty hidden>
                    <div class="empty-state">
                        <i class="bi bi-camera-reels"></i>
                        <p class="mb-0">No trending posts in this category yet. Be the first to start one!</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5" data-reveal>
                <a href="{{ route('feed') }}" class="btn-pov btn-pov-outline">
                    See the full feed <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         HOW IT WORKS (About)
         ===================================================================== --}}
    <section class="section how" id="about" aria-labelledby="howTitle">
        <span class="orb orb--orange" style="top: 30%; left: -12%; width: 380px; height: 380px; opacity: .25" aria-hidden="true"></span>

        <div class="container position-relative">
            <div class="text-center mb-5">
                <span class="section-eyebrow" data-reveal><i class="bi bi-lightning-charge"></i> How it works</span>
                <h2 class="section-title" id="howTitle" data-reveal style="--reveal-delay: .1s">
                    Three steps to your <span class="text-sunset">first take</span>
                </h2>
                <p class="section-lead mx-auto" data-reveal style="--reveal-delay: .2s">
                    Point of View is a school-built community for fans of stories in every format. No algorithms shouting at you, just people and opinions.
                </p>
            </div>

            <div class="how__steps">
                <span class="how__line d-none d-md-block" data-reveal="line" aria-hidden="true"></span>

                <ol class="row g-4 list-unstyled mb-0">
                    @foreach ($steps as $step)
                        <li class="col-md-4" data-reveal style="--reveal-delay: {{ 0.15 + $loop->index * 0.15 }}s">
                            <div class="how-step">
                                <div class="how-step__icon">
                                    <i class="bi {{ $step['icon'] }}" aria-hidden="true"></i>
                                    <span class="how-step__num">0{{ $loop->iteration }}</span>
                                </div>
                                <h3 class="how-step__title">{{ $step['title'] }}</h3>
                                <p class="mb-0">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         STATS COUNTERS
         TODO (Backend): real counts from the database.
         ===================================================================== --}}
    <section class="stats" aria-label="Community statistics">
        <div class="container">
            <div class="stats__band glass" data-reveal="zoom">
                <div class="row g-4 text-center">
                    @foreach ($stats as $stat)
                        <div class="col-6 col-lg-3">
                            <div class="stat">
                                <i class="bi {{ $stat['icon'] }} stat__icon" aria-hidden="true"></i>
                                <span class="stat__value" data-counter="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">{{ number_format($stat['value']) }}{{ $stat['suffix'] }}</span>
                                <span class="stat__label">{{ $stat['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         COMMUNITY VOICES (auto-sliding carousel)
         ===================================================================== --}}
    <section class="section voices-section" aria-labelledby="voicesTitle">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
                <div>
                    <span class="section-eyebrow" data-reveal><i class="bi bi-chat-heart"></i> Community voices</span>
                    <h2 class="section-title mb-0" id="voicesTitle" data-reveal style="--reveal-delay: .1s">
                        Loved by <span class="text-sunset">storytellers</span>
                    </h2>
                </div>
            </div>

            <div class="voices" data-carousel data-interval="4500" data-reveal style="--reveal-delay: .2s"
                 role="region" aria-roledescription="carousel" aria-label="Community testimonials">
                <div class="voices__viewport">
                    <div class="voices__track" data-carousel-track>
                        @foreach ($testimonials as $testimonial)
                            <div class="voices__slide" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}">
                                <figure class="pov-card voice-card" data-tilt="flat">
                                    <i class="bi bi-quote voice-card__quote" aria-hidden="true"></i>
                                    <blockquote class="voice-card__text">“{{ $testimonial['quote'] }}”</blockquote>
                                    <figcaption class="voice-card__author">
                                        <span class="pov-avatar" style="--avatar-hue: {{ $testimonial['user']['hue'] }}" aria-hidden="true">{{ $testimonial['user']['initials'] }}</span>
                                        <span>
                                            <strong>{{ $testimonial['user']['name'] }}</strong>
                                            <small>{{ $testimonial['role'] }}</small>
                                        </span>
                                        <span class="stars-display ms-auto" role="img" aria-label="5 out of 5">
                                            @for ($star = 0; $star < 5; $star++)<i class="bi bi-star-fill"></i>@endfor
                                        </span>
                                    </figcaption>
                                </figure>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="voices__controls">
                    <button type="button" class="carousel-arrow" data-carousel-prev aria-label="Previous testimonial"><i class="bi bi-arrow-left"></i></button>
                    <div class="carousel-dots" data-carousel-dots></div>
                    <button type="button" class="carousel-arrow" data-carousel-next aria-label="Next testimonial"><i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         FINAL CTA
         ===================================================================== --}}
    <section class="section pt-0" aria-labelledby="ctaTitle">
        <div class="container">
            <div class="cta-banner" data-reveal="zoom">
                <span class="cta-banner__ring cta-banner__ring--1" aria-hidden="true"></span>
                <span class="cta-banner__ring cta-banner__ring--2" aria-hidden="true"></span>
                <i class="bi bi-film cta-banner__reel float-slow" aria-hidden="true"></i>

                <div class="row align-items-center g-4 position-relative">
                    <div class="col-lg-7">
                        <h2 class="cta-banner__title" id="ctaTitle">Your next favorite story is one conversation away.</h2>
                        <p class="cta-banner__text mb-0">Create a free account, follow people with great taste and never finish a story alone again.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="cta-banner__actions">
                            <a href="{{ route('register') }}" class="btn-pov btn-pov-lg btn-pov-dark">
                                Create free account <i class="bi bi-arrow-right"></i>
                            </a>
                            <a href="{{ route('feed') }}" class="btn-pov btn-pov-lg btn-pov-dark-outline">
                                Browse the feed
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
