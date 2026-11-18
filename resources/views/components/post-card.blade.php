{{--
    Post card.
      <x-post-card :post="$post" />                  → compact (homepage, 3D tilt, short text)
      <x-post-card :post="$post" variant="full" />   → feed (spoiler blur, share, expandable comments)

    Like / bookmark toggles are handled globally in main.js; comments, spoilers
    and share live in feed.js.
--}}
@props(['post', 'variant' => 'compact'])

@php
    $isFull = $variant === 'full';
    $postId = $post['id'];
    $categoryIcons = [
        'movies' => 'bi-film',
        'series' => 'bi-tv',
        'anime' => 'bi-stars',
        'manga' => 'bi-journal-richtext',
        'books' => 'bi-book',
    ];
    $formatCount = fn (int $count): string => $count >= 1000
        ? rtrim(rtrim(number_format($count / 1000, 1), '0'), '.') . 'K'
        : (string) $count;
    $currentUser = \App\Support\MockData::currentUser();
@endphp

<article
    {{ $attributes->class(['pov-card', 'post-card', "post-card--{$variant}"]) }}
    data-category="{{ $post['category'] }}"
    data-tilt="{{ $isFull ? 'flat' : '' }}"
    aria-labelledby="post-{{ $postId }}-title"
>
    <header class="post-card__head">
        <span class="pov-avatar" style="--avatar-hue: {{ $post['user']['hue'] }}" aria-hidden="true">{{ $post['user']['initials'] }}</span>
        <div class="post-card__author">
            <span class="post-card__name">{{ $post['user']['name'] }}</span>
            <span class="post-card__meta">{{ '@' . $post['user']['handle'] }} · <time>{{ $post['time'] }}</time></span>
        </div>
        <span class="pov-badge ms-auto" data-category="{{ $post['category'] }}">{{ ucfirst($post['category']) }}</span>
    </header>

    <div class="post-card__work">
        <i class="bi {{ $categoryIcons[$post['category']] ?? 'bi-collection-play' }}" aria-hidden="true"></i>
        <strong>{{ $post['work'] }}</strong>
        <span class="stars-display" role="img" aria-label="Rated {{ $post['rating'] }} out of 5">
            @for ($star = 1; $star <= 5; $star++)
                <i class="bi {{ $star <= $post['rating'] ? 'bi-star-fill' : 'bi-star' }}"></i>
            @endfor
        </span>
    </div>

    <h3 class="post-card__title" id="post-{{ $postId }}-title">{{ $post['title'] }}</h3>

    @if ($isFull && $post['spoiler'])
        <div class="post-card__body spoiler" data-spoiler>
            <p class="spoiler__text mb-0">{{ $post['body'] }}</p>
            <button type="button" class="spoiler__reveal" data-spoiler-reveal>
                <i class="bi bi-eye-slash"></i> Contains spoilers · click to reveal
            </button>
        </div>
    @else
        <p class="post-card__body mb-0">{{ $post['body'] }}</p>
    @endif

    <footer class="post-card__actions">
        <button
            type="button"
            class="action-btn {{ $post['liked'] ? 'is-active' : '' }}"
            data-action="like"
            data-post-id="{{ $postId }}"
            aria-pressed="{{ $post['liked'] ? 'true' : 'false' }}"
            aria-label="Like"
        >
            <i class="bi {{ $post['liked'] ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            <span data-count="{{ $post['likes'] }}">{{ $formatCount($post['likes']) }}</span>
        </button>

        @if ($isFull)
            <button type="button" class="action-btn" data-comment-toggle aria-expanded="false" aria-controls="comments-{{ $postId }}" aria-label="Show comments">
                <i class="bi bi-chat"></i> <span>{{ $formatCount($post['comments_count']) }}</span>
            </button>
            <button type="button" class="action-btn" data-share data-post-title="{{ $post['title'] }}" aria-label="Share">
                <i class="bi bi-send"></i> <span class="d-none d-sm-inline">Share</span>
            </button>
        @else
            <a href="{{ route('feed') }}" class="action-btn" aria-label="{{ $post['comments_count'] }} comments, open discussion">
                <i class="bi bi-chat"></i> <span>{{ $formatCount($post['comments_count']) }}</span>
            </a>
        @endif

        <button
            type="button"
            class="action-btn ms-auto {{ $post['bookmarked'] ? 'is-active' : '' }}"
            data-action="bookmark"
            data-post-id="{{ $postId }}"
            aria-pressed="{{ $post['bookmarked'] ? 'true' : 'false' }}"
            aria-label="Bookmark"
        >
            <i class="bi {{ $post['bookmarked'] ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
        </button>
    </footer>

    @if ($isFull)
        {{-- Expandable comments (grid-rows trick for a smooth height animation) --}}
        <div class="post-comments" id="comments-{{ $postId }}" data-comments inert>
            <div class="post-comments__inner">
                <ul class="post-comments__list" data-comment-list>
                    @foreach ($post['comments'] as $comment)
                        <li class="comment">
                            <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: {{ $comment['user']['hue'] }}" aria-hidden="true">{{ $comment['user']['initials'] }}</span>
                            <div class="comment__bubble">
                                <span class="comment__name">{{ $comment['user']['name'] }}</span>
                                <p class="comment__text">{{ $comment['text'] }}</p>
                                <span class="comment__meta">{{ $comment['time'] }} · <button type="button" class="comment__link">Like</button> · <button type="button" class="comment__link">Reply</button></span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- TODO (Backend): action="{{ '/posts/' . $postId . '/comments' }}" method="POST" with @csrf --}}
                <form class="comment-form" data-comment-form novalidate>
                    <span class="pov-avatar pov-avatar--sm" style="--avatar-hue: {{ $currentUser['hue'] }}" aria-hidden="true">{{ $currentUser['initials'] }}</span>
                    <label class="visually-hidden" for="comment-input-{{ $postId }}">Write a comment</label>
                    <input type="text" id="comment-input-{{ $postId }}" class="comment-form__input" placeholder="Write a comment…" maxlength="300" autocomplete="off">
                    <button type="submit" class="comment-form__send" aria-label="Send comment"><i class="bi bi-send-fill"></i></button>
                </form>
            </div>
        </div>
    @endif
</article>
