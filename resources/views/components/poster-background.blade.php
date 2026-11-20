{{--
    Animated poster wall.
    Each row is rendered twice (original + clone) so the CSS marquee can loop
    seamlessly by translating the track by exactly -50%.
    The whole block is decorative, so it is hidden from screen readers.
--}}
<div {{ $attributes->class(['poster-bg', "poster-bg--{$variant}"]) }} aria-hidden="true">
    <div class="poster-bg__stage">
        @foreach ($rows as $row)
            <div
                class="poster-row {{ $row['reverse'] ? 'poster-row--reverse' : '' }}"
                style="--row-duration: {{ $row['duration'] }}s"
                data-poster-row
            >
                @foreach ([1, 2] as $copy)
                    @foreach ($row['posters'] as $poster)
                        <x-poster-card :poster="$poster" />
                    @endforeach
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Readability overlay + warm sunset glow on the edges --}}
    <div class="poster-bg__overlay"></div>
    <div class="poster-bg__glow"></div>
</div>
