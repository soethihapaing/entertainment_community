@extends('layouts.app')

@section('title', 'Community Feed')
@section('body_class', 'page-feed')

@push('head')
    {{-- Runs before first paint: continue the intro transition if we just came from login --}}
    <script>
        try {
            var povHandoff = JSON.parse(sessionStorage.getItem('pov:intro-handoff') || 'null');
            if (povHandoff && Date.now() - povHandoff.at < 15000) {
                document.documentElement.classList.add('intro-handoff');
                document.documentElement.dataset.introHandoff = povHandoff.mode;
            } else {
                sessionStorage.removeItem('pov:intro-handoff');
            }
        } catch (e) {}
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/feed.css') }}">
    <link rel="stylesheet" href="{{ asset('css/intro.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/feed.js') }}"></script>
    <script src="{{ asset('js/intro.js') }}"></script>
@endpush

@section('content')
    @include('partials.intro-arrival')

    {{-- Same poster wall, but fixed, darker and slower --}}
    <x-poster-background variant="subtle" />

    <div class="feed-page">
        <div class="container-xl">
            <div class="row g-4">

                {{-- =============================================================
                     LEFT SIDEBAR — profile, menu, category filter
                     TODO (Backend): $user = auth()->user()
                     ============================================================= --}}
                <aside class="col-lg-3 d-none d-lg-block" aria-label="Your profile and menu">
                    <div class="feed-sticky">
                        <section class="pov-card profile-card" data-load style="--delay: .05s">
                            <div class="profile-card__cover" aria-hidden="true"></div>
                            <div class="profile-card__body">
                                <span class="pov-avatar pov-avatar--lg profile-card__avatar" style="--avatar-hue: {{ $user['hue'] }}" aria-hidden="true">{{ $user['initials'] }}</span>
                                <h2 class="profile-card__name">{{ $user['name'] }}</h2>
                                <span class="profile-card__handle">{{ '@' . $user['handle'] }}</span>
                                <p class="profile-card__bio">{{ $user['bio'] }}</p>
                                <dl class="profile-card__stats">
                                    @foreach ($user['stats'] as $stat)
                                        <div>
                                            <dt>{{ $stat['label'] }}</dt>
                                            <dd>{{ $stat['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        </section>

                        <nav class="pov-card side-menu" aria-label="Feed menu" data-load style="--delay: .12s">
                            <a href="{{ route('feed') }}" class="side-menu__link is-active" aria-current="page"><i class="bi bi-house-door"></i> Feed</a>
                            <a href="#" class="side-menu__link" data-toast="Your posts page is coming soon." data-toast-title="My Posts"><i class="bi bi-file-earmark-text"></i> My Posts</a>
                            <a href="#" class="side-menu__link" data-toast="You have 4 saved posts." data-toast-title="Bookmarks"><i class="bi bi-bookmark"></i> Bookmarks</a>
                            <a href="#" class="side-menu__link" data-toast="Mika and 2 others liked your review." data-toast-title="Notifications">
                                <i class="bi bi-bell"></i> Notifications <span class="side-menu__badge">3</span>
                            </a>
                            <a href="#" class="side-menu__link" data-toast="Settings will be available after login is connected." data-toast-title="Settings"><i class="bi bi-gear"></i> Settings</a>
                        </nav>

                        <section class="pov-card side-filter" aria-labelledby="filterTitle" data-load style="--delay: .2s">
                            <h2 class="widget-title" id="filterTitle"><i class="bi bi-funnel"></i> Categories</h2>
                            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Filter feed by category">
                                <button type="button" class="pov-chip is-active" data-feed-category="all" aria-pressed="true"><i class="bi bi-grid-3x3-gap"></i> All</button>
                                @foreach ($categories as $category)
                                    <button type="button" class="pov-chip" data-feed-category="{{ $category['slug'] }}" aria-pressed="false">
                                        <i class="bi {{ $category['icon'] }}"></i> {{ $category['name'] }}
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </aside>

                {{-- =============================================================
                     CENTER — greeting, composer, tabs, posts
                     ============================================================= --}}
                <div class="col-12 col-lg-9 col-xl-6">
                    <header class="feed-greeting" data-load style="--delay: .05s">
                        <h1 class="feed-greeting__title"><span data-greeting>Hello</span>, {{ explode(' ', $user['name'])[0] }} <span class="feed-greeting__wave" aria-hidden="true">👋</span></h1>
                        <p class="mb-0">Here's what your community is talking about tonight.</p>
                    </header>

                    {{-- Category chips for small screens (the sidebar is hidden there) --}}
                    <div class="feed-mobile-filter d-lg-none" role="group" aria-label="Filter feed by category" data-load style="--delay: .1s">
                        <button type="button" class="pov-chip is-active" data-feed-category="all" aria-pressed="true">All</button>
                        @foreach ($categories as $category)
                            <button type="button" class="pov-chip" data-feed-category="{{ $category['slug'] }}" aria-pressed="false">
                                <i class="bi {{ $category['icon'] }}"></i> {{ $category['name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Post composer. TODO (Backend): POST to route('posts.store') with @csrf --}}
                    <section class="pov-card composer" data-tilt="flat" aria-labelledby="composerTitle" data-load style="--delay: .15s" id="composer">
                        <form data-composer novalidate>
                            <div class="composer__top">
                                <span class="pov-avatar" style="--avatar-hue: {{ $user['hue'] }}" aria-hidden="true">{{ $user['initials'] }}</span>
                                <div class="composer__field" data-field>
                                    <h2 class="composer__title" id="composerTitle">Share your point of view</h2>
                                    <label class="visually-hidden" for="composerText">Your review or opinion</label>
                                    <textarea id="composerText" class="composer__text" rows="2" maxlength="500"
                                              placeholder="What did you watch or read today? Tell us how it made you feel…" data-composer-text></textarea>
                                </div>
                            </div>

                            <div class="composer__meta">
                                <div class="composer__work">
                                    <i class="bi bi-collection-play" aria-hidden="true"></i>
                                    <label class="visually-hidden" for="composerWork">Title of the movie, series, anime, manga or book</label>
                                    <input type="text" id="composerWork" placeholder="Title (e.g. Spirited Away)" maxlength="80" data-composer-work>
                                </div>

                                <label class="visually-hidden" for="composerCategory">Category</label>
                                <select id="composerCategory" class="pov-select composer__select" data-composer-category>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category['slug'] }}">{{ $category['name'] }}</option>
                                    @endforeach
                                </select>

                                <div class="rating" role="group" aria-label="Your rating" data-rating data-value="0">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <button type="button" class="rating__star" data-star="{{ $star }}" aria-label="Rate {{ $star }} out of 5" aria-pressed="false">
                                            <i class="bi bi-star-fill"></i>
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            <div class="composer__bottom">
                                <label class="pov-switch">
                                    <input type="checkbox" data-composer-spoiler> <span><i class="bi bi-eye-slash"></i> Spoiler</span>
                                </label>
                                <button type="button" class="composer__tool" aria-label="Add image" data-toast="Image uploads arrive with the backend." data-toast-title="Media"><i class="bi bi-image"></i></button>
                                <button type="button" class="composer__tool" aria-label="Add emoji" data-toast="Emoji picker coming soon 🎬" data-toast-title="Emoji"><i class="bi bi-emoji-smile"></i></button>
                                <span class="composer__count ms-auto" data-composer-count>0 / 500</span>
                                <button type="submit" class="btn-pov btn-pov-primary btn-pov-sm">
                                    Post <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </section>

                    {{-- Feed tabs with a sliding indicator --}}
                    <div class="feed-tabs" role="tablist" aria-label="Sort the feed" data-feed-tabs data-load style="--delay: .22s">
                        <button type="button" role="tab" class="feed-tabs__btn is-active" aria-selected="true" data-feed-tab="latest"><i class="bi bi-clock"></i> Latest</button>
                        <button type="button" role="tab" class="feed-tabs__btn" aria-selected="false" data-feed-tab="trending"><i class="bi bi-fire"></i> Trending</button>
                        <button type="button" role="tab" class="feed-tabs__btn" aria-selected="false" data-feed-tab="following"><i class="bi bi-people"></i> Following</button>
                        <span class="feed-tabs__indicator" aria-hidden="true"></span>
                    </div>

                    {{-- Skeletons are shown first, then swapped for the posts by feed.js --}}
                    <div class="feed-skeletons" data-feed-skeletons aria-hidden="true">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="pov-card skeleton-card">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="skeleton" style="width: 44px; height: 44px; border-radius: 50%"></span>
                                    <div class="flex-grow-1">
                                        <span class="skeleton d-block mb-2" style="width: 40%; height: 12px"></span>
                                        <span class="skeleton d-block" style="width: 25%; height: 10px"></span>
                                    </div>
                                </div>
                                <span class="skeleton d-block mb-2" style="width: 75%; height: 16px"></span>
                                <span class="skeleton d-block mb-2" style="width: 100%; height: 10px"></span>
                                <span class="skeleton d-block mb-2" style="width: 92%; height: 10px"></span>
                                <span class="skeleton d-block" style="width: 60%; height: 10px"></span>
                            </div>
                        @endfor
                    </div>

                    {{-- TODO (Backend): @forelse ($posts as $post) ... with pagination links --}}
                    <div class="feed-list" id="feedList" aria-live="polite" aria-busy="true" data-feed-list>
                        @foreach ($posts as $post)
                            <div class="feed-item"
                                 data-feed-item
                                 data-category="{{ $post['category'] }}"
                                 data-likes="{{ $post['likes'] }}"
                                 data-order="{{ $loop->index }}"
                                 data-following="{{ in_array($post['user']['handle'], $followingHandles, true) ? 'true' : 'false' }}">
                                <x-post-card :post="$post" variant="full" />
                            </div>
                        @endforeach

                        <div class="empty-state" data-feed-empty hidden>
                            <i class="bi bi-camera-reels" aria-hidden="true"></i>
                            <p class="mb-0">Nothing here yet. Try another category or start the conversation yourself!</p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn-pov btn-pov-ghost" data-load-more>
                            <span class="btn-label"><i class="bi bi-arrow-down-circle"></i> Load more</span>
                            <span class="btn-spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>

                {{-- =============================================================
                     RIGHT SIDEBAR — search, trending, suggestions, now watching
                     ============================================================= --}}
                <aside class="col-xl-3 d-none d-xl-block" aria-label="Trending and suggestions">
                    <div class="feed-sticky">
                        <form class="feed-search" role="search" data-load style="--delay: .05s" onsubmit="event.preventDefault(); POV.toast('Search will work once the backend is connected.', { title: 'Search' });">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <label class="visually-hidden" for="feedSearch">Search Point of View</label>
                            <input type="search" id="feedSearch" placeholder="Search titles, people, tags…">
                            <kbd>/</kbd>
                        </form>

                        <section class="pov-card widget" aria-labelledby="trendingTopicsTitle" data-load style="--delay: .12s">
                            <h2 class="widget-title" id="trendingTopicsTitle"><i class="bi bi-graph-up-arrow"></i> Trending topics</h2>
                            <ol class="topic-list">
                                @foreach ($trendingTopics as $topic)
                                    <li>
                                        <a href="#" class="topic" data-toast="Showing posts tagged #{{ $topic['tag'] }} (demo)." data-toast-title="#{{ $topic['tag'] }}">
                                            <span class="topic__rank">{{ $loop->iteration }}</span>
                                            <span class="topic__text">
                                                <strong>#{{ $topic['tag'] }}</strong>
                                                <small>{{ $topic['category'] }} · {{ $topic['posts'] }} posts</small>
                                            </span>
                                            <i class="bi bi-chevron-right topic__arrow" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </section>

                        <section class="pov-card widget" aria-labelledby="suggestTitle" data-load style="--delay: .2s">
                            <h2 class="widget-title" id="suggestTitle"><i class="bi bi-person-plus"></i> Who to follow</h2>
                            <ul class="suggest-list">
                                @foreach ($suggestedUsers as $suggested)
                                    <li class="suggest">
                                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: {{ $suggested['hue'] }}" aria-hidden="true">{{ $suggested['initials'] }}</span>
                                        <span class="suggest__text">
                                            <strong>{{ $suggested['name'] }}</strong>
                                            <small>{{ $suggested['bio'] }}</small>
                                        </span>
                                        {{-- TODO (Backend): POST /users/{id}/follow --}}
                                        <button type="button" class="follow-btn" data-follow aria-pressed="false" aria-label="Follow {{ $suggested['name'] }}">
                                            <i class="bi bi-plus-lg"></i><span>Follow</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </section>

                        <section class="pov-card widget" aria-labelledby="watchingTitle" data-load style="--delay: .28s">
                            <h2 class="widget-title" id="watchingTitle"><i class="bi bi-play-btn"></i> Now watching / reading</h2>
                            <ul class="watching-list">
                                @foreach ($nowWatching as $item)
                                    <li class="watching">
                                        <span class="watching__poster"
                                              style="--poster-from: {{ $item['poster']['from'] }}; --poster-to: {{ $item['poster']['to'] }}"
                                              aria-hidden="true">
                                            @if ($item['poster']['image'])
                                                <img src="{{ $item['poster']['image'] }}" alt="">
                                            @else
                                                <span lang="ja">{{ mb_substr($item['poster']['jp'], 0, 2) }}</span>
                                            @endif
                                        </span>
                                        <span class="watching__info">
                                            <strong>{{ $item['title'] }}</strong>
                                            <small>{{ $item['type'] }} · {{ $item['detail'] }}</small>
                                            <span class="progress-line" role="progressbar" aria-label="{{ $item['title'] }} progress"
                                                  aria-valuenow="{{ $item['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                                <span style="--progress: {{ $item['progress'] }}%" data-progress></span>
                                            </span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    {{-- =================================================================
         CHAT / DISCUSSION PANEL (UI only)
         TODO (Backend): real-time messages via Laravel Reverb or Pusher.
         ================================================================= --}}
    <button type="button" class="chat-fab d-none d-lg-grid" data-chat-toggle aria-controls="chatPanel" aria-expanded="false" aria-label="Open discussion room">
        <i class="bi bi-chat-heart-fill"></i>
        <span class="chat-fab__badge" data-chat-badge>4</span>
    </button>

    <aside class="chat-panel" id="chatPanel" aria-label="Discussion room: Anime Night Club" inert>
        <header class="chat-panel__head">
            <span class="chat-panel__icon" aria-hidden="true"><i class="bi bi-stars"></i></span>
            <div class="flex-grow-1">
                <h2 class="chat-panel__title">Anime Night Club</h2>
                <small><span class="live-dot d-inline-block me-1"></span> 128 watching now</small>
            </div>
            <button type="button" class="btn-close-pov" data-chat-toggle aria-label="Close discussion room"><i class="bi bi-x-lg"></i></button>
        </header>

        <div class="chat-panel__messages" data-chat-messages aria-live="polite">
            @foreach ($chatMessages as $message)
                <div class="chat-msg {{ $message['mine'] ? 'chat-msg--mine' : '' }}">
                    @unless ($message['mine'])
                        <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: {{ $message['user']['hue'] }}; --size: 30px" aria-hidden="true">{{ $message['user']['initials'] }}</span>
                    @endunless
                    <div class="chat-msg__bubble">
                        @unless ($message['mine'])
                            <span class="chat-msg__name">{{ $message['user']['name'] }}</span>
                        @endunless
                        <p class="mb-0">{{ $message['text'] }}</p>
                        <time class="chat-msg__time">{{ $message['time'] }}</time>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="chat-typing" data-chat-typing hidden>
            <span class="chat-typing__dots" aria-hidden="true"><i></i><i></i><i></i></span>
            <span>Mika is typing…</span>
        </div>

        <form class="chat-panel__form" data-chat-form>
            <label class="visually-hidden" for="chatInput">Message</label>
            <input type="text" id="chatInput" placeholder="Say something nice…" autocomplete="off" maxlength="200">
            <button type="submit" class="chat-send" aria-label="Send message"><i class="bi bi-send-fill"></i></button>
        </form>
    </aside>

    {{-- Mobile bottom navigation --}}
    <nav class="feed-bottom-nav d-lg-none" aria-label="Quick navigation">
        <a href="{{ route('feed') }}" class="is-active" aria-current="page"><i class="bi bi-house-door-fill"></i><span>Feed</span></a>
        <a href="{{ route('home') }}#trending"><i class="bi bi-compass"></i><span>Explore</span></a>
        <a href="#composer" class="feed-bottom-nav__post" data-focus-composer aria-label="Write a post"><i class="bi bi-plus-lg"></i></a>
        <button type="button" data-chat-toggle aria-controls="chatPanel" aria-expanded="false"><i class="bi bi-chat-dots"></i><span>Chat</span></button>
        <a href="#" data-toast="Your profile page is coming soon." data-toast-title="Profile"><i class="bi bi-person"></i><span>Profile</span></a>
    </nav>
@endsection
