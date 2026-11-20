{{--
    A single poster.
    - If public/images/posters/{slug}.(webp|jpg|jpeg|png) exists → real <img>.
    - Otherwise → gradient placeholder with the title (no copyrighted art needed).
--}}
@props(['poster'])

<figure
    {{ $attributes->class(['poster-card', 'poster-card--image' => $poster['image']]) }}
    style="--poster-from: {{ $poster['from'] }}; --poster-to: {{ $poster['to'] }}; --poster-accent: {{ $poster['accent'] }};"
>
    @if ($poster['image'])
        <img src="{{ $poster['image'] }}" alt="{{ $poster['title'] }} poster" loading="lazy" decoding="async">
    @else
        <div class="poster-art poster-art--{{ $poster['motif'] }}">
            <span class="poster-art__shape"></span>
            <span class="poster-art__jp" lang="ja">{{ $poster['jp'] }}</span>
        </div>
    @endif

    <figcaption class="poster-card__meta">
        <span class="poster-card__title">{{ $poster['title'] }}</span>
        <span class="poster-card__sub">{{ $poster['year'] }} · {{ $poster['type'] }}</span>
    </figcaption>
</figure>
