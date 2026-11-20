<?php

namespace App\Support;

/**
 * Data for the "Enter Point of View" intro sequence (public/js/intro.js).
 *
 * Optional files are detected on the server so the browser never requests
 * images or sounds that do not exist (no 404s in the console):
 *   - public/images/intro/joker.png     → shown on the account card face
 *   - public/audio/whoosh.mp3, hit.mp3  → replace the synthesized sounds
 *   - public/images/posters/{slug}.*    → poster faces (see MockData::posters())
 */
class IntroAssets
{
    /**
     * @return array{posters: list<array<string, mixed>>, joker: string|null, audio: array{whoosh: string|null, hit: string|null}, feedUrl: string}
     */
    public static function toArray(): array
    {
        return [
            'posters' => array_map(
                fn (array $poster): array => [
                    'title' => $poster['title'],
                    'jp' => $poster['jp'],
                    'year' => $poster['year'],
                    'type' => $poster['type'],
                    'from' => $poster['from'],
                    'to' => $poster['to'],
                    'accent' => $poster['accent'],
                    'motif' => $poster['motif'],
                    'image' => $poster['image'],
                ],
                MockData::posters(),
            ),
            'joker' => self::publicUrl('images/intro/joker.png'),
            'audio' => [
                'whoosh' => self::publicUrl('audio/whoosh.mp3'),
                'hit' => self::publicUrl('audio/hit.mp3'),
            ],
            'feedUrl' => route('feed'),
        ];
    }

    /**
     * Public URL of a file inside public/, or null when it does not exist.
     */
    private static function publicUrl(string $relativePath): ?string
    {
        return file_exists(public_path($relativePath)) ? asset($relativePath) : null;
    }
}
