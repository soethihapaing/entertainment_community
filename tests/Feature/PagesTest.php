<?php

namespace Tests\Feature;

use App\Support\IntroAssets;
use App\Support\MockData;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_homepage_renders_all_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Share your Point of View.');
        $response->assertSee('Five worlds.', false);
        $response->assertSee('Trending');
        $response->assertSee('id="about"', false);
        $response->assertSee('data-carousel', false);
        $response->assertSee('poster-card', false);
    }

    public function test_login_page_opens_on_the_login_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Welcome back');
        $response->assertSee('data-mode="login"', false);
    }

    public function test_login_page_ships_the_intro_sequence_data_and_scripts(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('id="introData"', false);
        $response->assertSee('js/intro.js', false);
        $response->assertSee('js/intro-sound.js', false);
        $response->assertSee('data-sound-toggle', false);
        $response->assertViewHas('introAssets', fn (array $assets): bool => count($assets['posters']) === 16
            && $assets['feedUrl'] === route('feed'));
    }

    public function test_intro_assets_only_list_optional_files_that_exist(): void
    {
        $joker = public_path('images/intro/joker.png');
        $this->assertFileDoesNotExist($joker);
        $this->assertNull(IntroAssets::toArray()['joker']);

        File::put($joker, 'fake image');

        try {
            $this->assertSame(asset('images/intro/joker.png'), IntroAssets::toArray()['joker']);
        } finally {
            File::delete($joker);
        }
    }

    public function test_feed_page_includes_the_intro_arrival_overlay(): void
    {
        $response = $this->get(route('feed'));

        $response->assertOk();
        $response->assertSee('data-intro-arrival', false);
        $response->assertSee('pov:intro-handoff', false);
    }

    public function test_register_page_opens_on_the_register_form(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Join the story');
        $response->assertSee('data-mode="register"', false);
    }

    public function test_feed_page_shows_mock_posts_and_widgets(): void
    {
        $response = $this->get(route('feed'));

        $response->assertOk();
        $response->assertSee('Share your point of view');
        $response->assertSee('Frieren made me rethink');
        $response->assertSee('Trending topics');
        $response->assertSee('Anime Night Club');
    }

    public function test_poster_uses_gradient_fallback_until_an_image_is_added(): void
    {
        $slug = 'test-poster-'.uniqid();
        $path = public_path("images/posters/{$slug}.jpg");

        $this->assertNull(MockData::posterImage($slug));

        File::put($path, 'fake image');

        try {
            $this->assertSame(asset("images/posters/{$slug}.jpg"), MockData::posterImage($slug));
        } finally {
            File::delete($path);
        }
    }
}
