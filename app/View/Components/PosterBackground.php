<?php

namespace App\View\Components;

use App\Support\MockData;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Animated "poster wall" background.
 *
 * Usage:
 *   <x-poster-background />                    (hero: fills its parent)
 *   <x-poster-background variant="panel" />    (login left panel)
 *   <x-poster-background variant="subtle" />   (fixed, darker and slower, for the feed)
 */
class PosterBackground extends Component
{
    /**
     * Base duration (seconds) of each row's loop. Rows alternate direction.
     *
     * @var list<int>
     */
    private const ROW_DURATIONS = [70, 95, 60, 85];

    /**
     * Rows of posters, each with its own speed and direction.
     *
     * @var list<array{posters: list<array<string, mixed>>, duration: int, reverse: bool}>
     */
    public array $rows = [];

    public function __construct(public string $variant = 'hero')
    {
        $posters = MockData::posters();
        $speedFactor = $variant === 'subtle' ? 1.8 : 1;

        foreach (self::ROW_DURATIONS as $index => $duration) {
            $this->rows[] = [
                'posters' => $this->rotate($posters, $index * 5),
                'duration' => (int) round($duration * $speedFactor),
                'reverse' => $index % 2 === 1,
            ];
        }
    }

    /**
     * Shift the poster list so every row starts with a different title.
     *
     * @param  list<array<string, mixed>>  $posters
     * @return list<array<string, mixed>>
     */
    private function rotate(array $posters, int $offset): array
    {
        $offset %= count($posters);

        return [...array_slice($posters, $offset), ...array_slice($posters, 0, $offset)];
    }

    public function render(): View|Closure|string
    {
        return view('components.poster-background');
    }
}
