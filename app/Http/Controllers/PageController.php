<?php

namespace App\Http\Controllers;

use App\Support\MockData;
use Illuminate\Contracts\View\View;

/**
 * Public pages and the community feed.
 *
 * Right now every page receives static arrays from App\Support\MockData.
 * TODO (Backend): replace the MockData calls with Eloquent queries
 * (Post, Category, User models) once the MySQL tables exist.
 */
class PageController extends Controller
{
    /**
     * Landing page.
     */
    public function home(): View
    {
        return view('home', [
            'categories' => MockData::categories(),
            'trendingPosts' => MockData::trendingPosts(),
            'steps' => MockData::steps(),
            'stats' => MockData::stats(),
            'testimonials' => MockData::testimonials(),
        ]);
    }

    /**
     * Community feed (user-side page).
     *
     * TODO (Backend): protect this route with the "auth" middleware and load
     * the feed for auth()->user() instead of MockData::currentUser().
     */
    public function feed(): View
    {
        return view('feed', [
            'user' => MockData::currentUser(),
            'categories' => MockData::categories(),
            'posts' => MockData::feedPosts(),
            'followingHandles' => MockData::followingHandles(),
            'trendingTopics' => MockData::trendingTopics(),
            'suggestedUsers' => MockData::suggestedUsers(),
            'nowWatching' => array_map(
                fn (array $item): array => [...$item, 'poster' => MockData::poster($item['poster'])],
                MockData::nowWatching(),
            ),
            'chatMessages' => MockData::chatMessages(),
        ]);
    }
}
