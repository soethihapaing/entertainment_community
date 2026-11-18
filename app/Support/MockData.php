<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Static mock data for the "Point of View" frontend.
 *
 * Every method here returns plain arrays so the Blade views can be built
 * without a database. When the backend is ready, replace each call in the
 * controllers with real Eloquent queries (see the TODO notes on each method)
 * and keep the same array keys, or switch the views to model properties.
 */
class MockData
{
    /**
     * Image extensions checked (in order) inside public/images/posters/.
     *
     * @var list<string>
     */
    private const POSTER_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    /**
     * Cached poster list so the file system is only checked once per request.
     *
     * @var list<array<string, mixed>>|null
     */
    private static ?array $posterCache = null;

    /**
     * Famous Japanese films / anime used by the animated poster background.
     *
     * Drop a real poster at public/images/posters/{slug}.(webp|jpg|jpeg|png)
     * and it will be used automatically instead of the gradient placeholder.
     *
     * @return list<array{slug: string, title: string, jp: string, year: int, type: string, from: string, to: string, accent: string, motif: string, image: string|null}>
     */
    public static function posters(): array
    {
        if (self::$posterCache !== null) {
            return self::$posterCache;
        }

        $posters = [
            ['title' => 'Your Name', 'jp' => '君の名は。', 'year' => 2016, 'type' => 'Film', 'from' => '#1D3B8B', 'to' => '#FF8A5C', 'accent' => '#FFD7A8', 'motif' => 'sun'],
            ['title' => 'Spirited Away', 'jp' => '千と千尋の神隠し', 'year' => 2001, 'type' => 'Film', 'from' => '#7A1F2B', 'to' => '#F2B45A', 'accent' => '#FFE3A3', 'motif' => 'moon'],
            ['title' => 'Demon Slayer', 'jp' => '鬼滅の刃', 'year' => 2019, 'type' => 'Anime', 'from' => '#0F3D3E', 'to' => '#E0475B', 'accent' => '#7FE0C4', 'motif' => 'stripes'],
            ['title' => 'Attack on Titan', 'jp' => '進撃の巨人', 'year' => 2013, 'type' => 'Anime', 'from' => '#2A2320', 'to' => '#A3542B', 'accent' => '#E8C39E', 'motif' => 'wave'],
            ['title' => 'Weathering With You', 'jp' => '天気の子', 'year' => 2019, 'type' => 'Film', 'from' => '#2B6FD6', 'to' => '#9FD8FF', 'accent' => '#FFFFFF', 'motif' => 'sun'],
            ['title' => 'Akira', 'jp' => 'アキラ', 'year' => 1988, 'type' => 'Film', 'from' => '#3A0008', 'to' => '#FF3D3D', 'accent' => '#FFB4A8', 'motif' => 'stripes'],
            ['title' => 'Princess Mononoke', 'jp' => 'もののけ姫', 'year' => 1997, 'type' => 'Film', 'from' => '#1F4D2B', 'to' => '#C9A227', 'accent' => '#F3E7B0', 'motif' => 'moon'],
            ['title' => 'Josee, the Tiger and the Fish', 'jp' => 'ジョゼと虎と魚たち', 'year' => 2020, 'type' => 'Film', 'from' => '#0E5E6F', 'to' => '#F6A04D', 'accent' => '#BFF2F0', 'motif' => 'wave'],
            ['title' => 'Suzume', 'jp' => 'すずめの戸締まり', 'year' => 2022, 'type' => 'Film', 'from' => '#3A2A7A', 'to' => '#FF9E5E', 'accent' => '#FFE0C2', 'motif' => 'sun'],
            ['title' => 'Jujutsu Kaisen', 'jp' => '呪術廻戦', 'year' => 2020, 'type' => 'Anime', 'from' => '#140F3A', 'to' => '#6A3CFF', 'accent' => '#C9B8FF', 'motif' => 'stripes'],
            ['title' => 'One Piece', 'jp' => 'ワンピース', 'year' => 1999, 'type' => 'Anime', 'from' => '#0B4F8A', 'to' => '#FFCC4D', 'accent' => '#FFF2C4', 'motif' => 'wave'],
            ['title' => 'Death Note', 'jp' => 'デスノート', 'year' => 2006, 'type' => 'Anime', 'from' => '#050505', 'to' => '#8B0000', 'accent' => '#E6E6E6', 'motif' => 'moon'],
            ['title' => "Howl's Moving Castle", 'jp' => 'ハウルの動く城', 'year' => 2004, 'type' => 'Film', 'from' => '#384E8F', 'to' => '#F59AC0', 'accent' => '#FFE6F1', 'motif' => 'sun'],
            ['title' => 'Perfect Blue', 'jp' => 'パーフェクトブルー', 'year' => 1997, 'type' => 'Film', 'from' => '#12235C', 'to' => '#D61F69', 'accent' => '#9CC3FF', 'motif' => 'stripes'],
            ['title' => 'A Silent Voice', 'jp' => '聲の形', 'year' => 2016, 'type' => 'Film', 'from' => '#2E7FA8', 'to' => '#F7C6D9', 'accent' => '#FFFFFF', 'motif' => 'moon'],
            ['title' => 'Spy x Family', 'jp' => 'スパイファミリー', 'year' => 2022, 'type' => 'Anime', 'from' => '#2D5A3D', 'to' => '#F2A7B8', 'accent' => '#FFE9EE', 'motif' => 'wave'],
        ];

        return self::$posterCache = array_map(function (array $poster): array {
            $slug = Str::slug(str_replace("'", '', $poster['title']));

            return ['slug' => $slug, ...$poster, 'image' => self::posterImage($slug)];
        }, $posters);
    }

    /**
     * Find a poster by slug (used by post cards and the "Now watching" widget).
     *
     * @return array<string, mixed>|null
     */
    public static function poster(string $slug): ?array
    {
        return collect(self::posters())->firstWhere('slug', $slug);
    }

    /**
     * Return the public URL of a real poster image, or null if none was added.
     */
    public static function posterImage(string $slug): ?string
    {
        foreach (self::POSTER_EXTENSIONS as $extension) {
            $relativePath = "images/posters/{$slug}.{$extension}";

            if (file_exists(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        return null;
    }

    /**
     * The five content categories.
     *
     * TODO (Backend): Category::withCount('posts')->get()
     *
     * @return list<array{slug: string, name: string, icon: string, description: string, count: string}>
     */
    public static function categories(): array
    {
        return [
            ['slug' => 'movies', 'name' => 'Movies', 'icon' => 'bi-film', 'description' => 'Blockbusters, indie gems and the classics we keep coming back to.', 'count' => '12.4K'],
            ['slug' => 'series', 'name' => 'Series', 'icon' => 'bi-tv', 'description' => 'Binge-worthy shows and the wild fan theories they spark.', 'count' => '8.9K'],
            ['slug' => 'anime', 'name' => 'Anime', 'icon' => 'bi-stars', 'description' => 'From shōnen battles to soft slice-of-life comfort watches.', 'count' => '15.1K'],
            ['slug' => 'manga', 'name' => 'Manga', 'icon' => 'bi-journal-richtext', 'description' => 'Chapter drops, panel breakdowns and long-running sagas.', 'count' => '6.7K'],
            ['slug' => 'books', 'name' => 'Books', 'icon' => 'bi-book', 'description' => 'Novels, light novels and stories that stay with you.', 'count' => '5.2K'],
        ];
    }

    /**
     * Mock community members (used as post authors and suggestions).
     *
     * TODO (Backend): User model (name, username, avatar, bio).
     *
     * @return array<string, array{name: string, handle: string, initials: string, hue: int, bio: string}>
     */
    public static function users(): array
    {
        return [
            'mika' => ['name' => 'Mika Tanaka', 'handle' => 'mikawatches', 'initials' => 'MT', 'hue' => 18, 'bio' => 'Anime analyst · Frieren apologist'],
            'leo' => ['name' => 'Leo Martins', 'handle' => 'leoreels', 'initials' => 'LM', 'hue' => 220, 'bio' => 'IMAX or nothing'],
            'aye' => ['name' => 'Aye Chan', 'handle' => 'ayereads', 'initials' => 'AC', 'hue' => 330, 'bio' => 'Cozy books & rainy days'],
            'jun' => ['name' => 'Jun Park', 'handle' => 'junpanels', 'initials' => 'JP', 'hue' => 265, 'bio' => 'Reads manga one panel at a time'],
            'sofia' => ['name' => 'Sofia Reyes', 'handle' => 'sofiaseries', 'initials' => 'SR', 'hue' => 160, 'bio' => 'Slow-burn series enjoyer'],
            'naylin' => ['name' => 'Nay Lin', 'handle' => 'naylinfilms', 'initials' => 'NL', 'hue' => 40, 'bio' => 'Shinkai sunsets fan'],
            'yuki' => ['name' => 'Yuki Mori', 'handle' => 'yukiframes', 'initials' => 'YM', 'hue' => 195, 'bio' => 'Frame-by-frame film nerd'],
            'omar' => ['name' => 'Omar Haddad', 'handle' => 'omarpages', 'initials' => 'OH', 'hue' => 0, 'bio' => 'Fantasy books & big maps'],
            'lin' => ['name' => 'Lin Htet', 'handle' => 'linhtet.pov', 'initials' => 'LH', 'hue' => 290, 'bio' => 'Series marathoner'],
        ];
    }

    /**
     * Posts shown in the "Trending Discussions" section of the homepage.
     *
     * TODO (Backend): Post::with('user', 'category')->withCount('likes', 'comments')
     *                 ->orderByDesc('likes_count')->take(6)->get()
     *
     * @return list<array<string, mixed>>
     */
    public static function trendingPosts(): array
    {
        return array_slice(self::feedPosts(), 0, 6);
    }

    /**
     * Posts for the community feed (full version with comments).
     *
     * TODO (Backend): Post::with('user', 'category', 'comments.user')->latest()->paginate(10)
     *
     * @return list<array<string, mixed>>
     */
    public static function feedPosts(): array
    {
        $users = self::users();

        return [
            [
                'id' => 101, 'user' => $users['mika'], 'category' => 'anime',
                'work' => 'Frieren: Beyond Journey\'s End', 'rating' => 5, 'spoiler' => false,
                'title' => 'Frieren made me rethink what an "adventure" story can be',
                'body' => 'It starts where most fantasy stories end, and somehow every quiet scene hits harder than a boss fight. The way it handles time and memory is pure poetry.',
                'likes' => 2431, 'comments_count' => 318, 'time' => '2h ago', 'liked' => true, 'bookmarked' => false,
                'comments' => [
                    ['user' => $users['jun'], 'text' => 'The flashback structure is genius. Every episode feels like a memory.', 'time' => '1h'],
                    ['user' => $users['naylin'], 'text' => 'Episode 10 had no right to be that emotional.', 'time' => '48m'],
                ],
            ],
            [
                'id' => 102, 'user' => $users['leo'], 'category' => 'movies',
                'work' => 'Dune: Part Two', 'rating' => 5, 'spoiler' => false,
                'title' => 'Dune: Part Two is the reason we still need giant screens',
                'body' => 'The sound design alone deserves its own review. I felt the sandworm scene in my chest. Go see it as big and as loud as you possibly can.',
                'likes' => 1876, 'comments_count' => 204, 'time' => '4h ago', 'liked' => false, 'bookmarked' => true,
                'comments' => [
                    ['user' => $users['yuki'], 'text' => 'That black-and-white arena sequence though. Unreal cinematography.', 'time' => '3h'],
                ],
            ],
            [
                'id' => 103, 'user' => $users['aye'], 'category' => 'books',
                'work' => 'Before the Coffee Gets Cold', 'rating' => 4, 'spoiler' => false,
                'title' => 'Finished "Before the Coffee Gets Cold" in one sitting',
                'body' => 'Four small stories, one tiny café, and a lot of feelings. It reads like a gentle Japanese film. Perfect for a rainy afternoon with tea.',
                'likes' => 964, 'comments_count' => 87, 'time' => '6h ago', 'liked' => false, 'bookmarked' => false,
                'comments' => [
                    ['user' => $users['omar'], 'text' => 'Adding this to my list right now!', 'time' => '5h'],
                    ['user' => $users['sofia'], 'text' => 'The second story wrecked me.', 'time' => '4h'],
                ],
            ],
            [
                'id' => 104, 'user' => $users['jun'], 'category' => 'manga',
                'work' => 'Chainsaw Man — Part 2', 'rating' => 4, 'spoiler' => true,
                'title' => 'Chainsaw Man Part 2 is weirder, and I love it',
                'body' => 'The new lead changes the whole tone. Less chaos, more dread, and the panel layouts are getting experimental again. Fujimoto is cooking.',
                'likes' => 1302, 'comments_count' => 156, 'time' => '9h ago', 'liked' => true, 'bookmarked' => true,
                'comments' => [
                    ['user' => $users['mika'], 'text' => 'Those double-page spreads are pure cinema.', 'time' => '8h'],
                ],
            ],
            [
                'id' => 105, 'user' => $users['sofia'], 'category' => 'series',
                'work' => 'Shōgun', 'rating' => 5, 'spoiler' => true,
                'title' => 'Shōgun\'s slow-burn finale was worth every minute',
                'body' => 'No giant battle, just strategy, silence and one devastating reveal. It trusts the audience, and that is rare for a big-budget series.',
                'likes' => 1158, 'comments_count' => 132, 'time' => '12h ago', 'liked' => false, 'bookmarked' => false,
                'comments' => [
                    ['user' => $users['lin'], 'text' => 'Mariko\'s arc is one of the best in TV history.', 'time' => '10h'],
                    ['user' => $users['leo'], 'text' => 'The quiet ending is exactly why it works.', 'time' => '9h'],
                ],
            ],
            [
                'id' => 106, 'user' => $users['naylin'], 'category' => 'anime',
                'work' => 'Your Name', 'rating' => 5, 'spoiler' => false,
                'title' => 'Rewatched "Your Name" at 2am and cried again',
                'body' => 'The comet scene with RADWIMPS playing is still the most beautiful thing in animation. Every frame could be a wallpaper.',
                'likes' => 2087, 'comments_count' => 245, 'time' => '1d ago', 'liked' => false, 'bookmarked' => false,
                'comments' => [
                    ['user' => $users['aye'], 'text' => '"Sparkle" plays and I am gone every single time.', 'time' => '20h'],
                ],
            ],
        ];
    }

    /**
     * Steps for the "How it works" section.
     *
     * @return list<array{icon: string, title: string, text: string}>
     */
    public static function steps(): array
    {
        return [
            ['icon' => 'bi-play-circle', 'title' => 'Watch or Read', 'text' => 'Finish that movie, binge the series, or close the last page of a manga or book.'],
            ['icon' => 'bi-chat-quote', 'title' => 'Share your thoughts', 'text' => 'Post a review, rate it, and hide spoilers so nobody gets their story ruined.'],
            ['icon' => 'bi-people', 'title' => 'Discuss with others', 'text' => 'Join conversations, follow people with great taste and find your next favorite.'],
        ];
    }

    /**
     * Community statistics for the animated counters.
     *
     * TODO (Backend): User::count(), Post::count(), Comment::count() ...
     *
     * @return list<array{label: string, value: int, suffix: string, icon: string}>
     */
    public static function stats(): array
    {
        return [
            ['label' => 'Members', 'value' => 12500, 'suffix' => '+', 'icon' => 'bi-people-fill'],
            ['label' => 'Reviews', 'value' => 48200, 'suffix' => '+', 'icon' => 'bi-star-fill'],
            ['label' => 'Discussions', 'value' => 9800, 'suffix' => '+', 'icon' => 'bi-chat-dots-fill'],
            ['label' => 'Titles', 'value' => 3400, 'suffix' => '', 'icon' => 'bi-collection-play-fill'],
        ];
    }

    /**
     * "Community voices" testimonials for the homepage carousel.
     *
     * @return list<array{user: array<string, mixed>, quote: string, role: string}>
     */
    public static function testimonials(): array
    {
        $users = self::users();

        return [
            ['user' => $users['yuki'], 'role' => 'Film student', 'quote' => 'Finally a place where people actually talk about framing and color, not just star ratings.'],
            ['user' => $users['aye'], 'role' => 'Book club host', 'quote' => 'I found three new favorite novels in my first week. The spoiler tags are a lifesaver.'],
            ['user' => $users['jun'], 'role' => 'Manga reader', 'quote' => 'Chapter-drop discussions here feel like watching a match with friends.'],
            ['user' => $users['sofia'], 'role' => 'Series binger', 'quote' => 'The community is kind, nerdy and very passionate. Exactly my vibe.'],
            ['user' => $users['omar'], 'role' => 'Fantasy fan', 'quote' => 'Point of View made me brave enough to post my first long review.'],
        ];
    }

    /**
     * The signed-in user shown on the feed page.
     *
     * TODO (Backend): auth()->user() with followers / posts counts.
     *
     * @return array{name: string, handle: string, initials: string, hue: int, bio: string, stats: list<array{label: string, value: string}>}
     */
    public static function currentUser(): array
    {
        return [
            'name' => 'Hana Kyaw',
            'handle' => 'hanapov',
            'initials' => 'HK',
            'hue' => 22,
            'bio' => 'Night-owl cinephile · Ghibli forever · currently lost in manga.',
            'stats' => [
                ['label' => 'Posts', 'value' => '128'],
                ['label' => 'Followers', 'value' => '2.3K'],
                ['label' => 'Following', 'value' => '312'],
            ],
        ];
    }

    /**
     * Trending hashtags for the feed sidebar.
     *
     * @return list<array{tag: string, category: string, posts: string}>
     */
    public static function trendingTopics(): array
    {
        return [
            ['tag' => 'Frieren', 'category' => 'Anime', 'posts' => '4.2K'],
            ['tag' => 'DunePartTwo', 'category' => 'Movies', 'posts' => '3.1K'],
            ['tag' => 'ShogunFinale', 'category' => 'Series', 'posts' => '2.6K'],
            ['tag' => 'ChainsawMan', 'category' => 'Manga', 'posts' => '1.9K'],
            ['tag' => 'CozyReads', 'category' => 'Books', 'posts' => '1.1K'],
        ];
    }

    /**
     * Handles the signed-in user follows (drives the "Following" feed tab).
     *
     * TODO (Backend): auth()->user()->following()->pluck('username')
     *
     * @return list<string>
     */
    public static function followingHandles(): array
    {
        return ['mikawatches', 'junpanels', 'ayereads'];
    }

    /**
     * Suggested users to follow.
     *
     * TODO (Backend): users the current user does not follow yet.
     *
     * @return list<array<string, mixed>>
     */
    public static function suggestedUsers(): array
    {
        $users = self::users();

        return [$users['yuki'], $users['omar'], $users['lin']];
    }

    /**
     * "Now watching / reading" widget items.
     *
     * @return list<array{title: string, type: string, progress: int, detail: string, poster: string}>
     */
    public static function nowWatching(): array
    {
        return [
            ['title' => 'Spy x Family', 'type' => 'Anime', 'progress' => 72, 'detail' => 'S2 · Ep 9 of 12', 'poster' => 'spy-x-family'],
            ['title' => 'One Piece', 'type' => 'Manga', 'progress' => 45, 'detail' => 'Ch. 1045', 'poster' => 'one-piece'],
            ['title' => "Howl's Moving Castle", 'type' => 'Book', 'progress' => 30, 'detail' => 'Page 96 of 320', 'poster' => 'howls-moving-castle'],
        ];
    }

    /**
     * Messages for the slide-in discussion panel.
     *
     * TODO (Backend): real-time chat (Laravel Reverb / Pusher) later.
     *
     * @return list<array{user: array<string, mixed>, text: string, time: string, mine: bool}>
     */
    public static function chatMessages(): array
    {
        $users = self::users();

        return [
            ['user' => $users['mika'], 'text' => 'Who else is watching the new episode tonight?', 'time' => '20:41', 'mine' => false],
            ['user' => $users['jun'], 'text' => 'Me! No spoilers until 10pm please 🙏', 'time' => '20:42', 'mine' => false],
            ['user' => self::currentUser(), 'text' => 'Same, popcorn is ready 🍿', 'time' => '20:43', 'mine' => true],
            ['user' => $users['naylin'], 'text' => 'The OST in the last episode was so good.', 'time' => '20:45', 'mine' => false],
        ];
    }
}
