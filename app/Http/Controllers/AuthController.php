<?php

namespace App\Http\Controllers;

use App\Support\IntroAssets;
use Illuminate\Contracts\View\View;

/**
 * Login / register screens (UI only for now).
 *
 * TODO (Backend): add these methods when Laravel auth is connected:
 *   - login(Request $request):    validate, Auth::attempt($credentials, $remember),
 *                                 $request->session()->regenerate(), redirect()->intended(route('feed'))
 *   - register(Request $request): validate, User::create([...]), Auth::login($user), redirect to feed
 *   - logout(Request $request):   Auth::logout(), invalidate session, redirect home
 * Or install a starter kit (Laravel Breeze / Fortify) and point the forms at its routes.
 */
class AuthController extends Controller
{
    /**
     * Show the card with the login form visible.
     */
    public function showLogin(): View
    {
        return view('auth.login', ['mode' => 'login', 'introAssets' => IntroAssets::toArray()]);
    }

    /**
     * Same card, but opened on the register side.
     */
    public function showRegister(): View
    {
        return view('auth.login', ['mode' => 'register', 'introAssets' => IntroAssets::toArray()]);
    }
}
