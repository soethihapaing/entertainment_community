{{-- Category card (Movies, Series, Anime, Manga, Books) with tilt + glow. --}}
@props(['category'])

<a
    href="{{ route('feed', ['category' => $category['slug']]) }}"
    {{ $attributes->class(['pov-card', 'category-card']) }}
    data-tilt
    data-category="{{ $category['slug'] }}"
    aria-label="{{ $category['name'] }}: {{ $category['count'] }} discussions"
>
    <span class="category-card__bg-icon" aria-hidden="true"><i class="bi {{ $category['icon'] }}"></i></span>

    <span class="category-card__icon" aria-hidden="true">
        <i class="bi {{ $category['icon'] }} zoom-on-hover"></i>
    </span>

    <h3 class="category-card__name">{{ $category['name'] }}</h3>
    <p class="category-card__text">{{ $category['description'] }}</p>

    <span class="category-card__meta">
        {{ $category['count'] }} discussions <i class="bi bi-arrow-up-right"></i>
    </span>
</a>
