<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show Login Form
     */
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Login for both API and Web
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => 'required|string', // username or email
            'password' => 'required|string',
        ]);

        $loginInput = $credentials['login'];

        // Find user by email OR username
        $user = User::where('email', $loginInput)
                    ->orWhere('username', $loginInput)
                    ->first();

        // Validate Credentials
        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid Login Credentials. Please check and retry.',
                ], 401);
            }
            return back()->withErrors(['login' => 'Invalid username or password. Please try again.'])->withInput();
        }

        // Check Account Status
        if ($user->account_status !== 'active') {
            $msg = "Your account is {$user->account_status}, so you cannot sign in.";
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Access Denied: Your account is {$user->account_status}.",
                ], 403);
            }
            return back()->withErrors(['login' => $msg])->withInput();
        }

        // Login user in session
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Logged in successfully.',
                'data'    => [
                    'user_id'        => $user->user_id,
                    'username'       => $user->username,
                    'full_name'      => $user->full_name,
                    'email'          => $user->email,
                    'role'           => $user->role,
                    'account_status' => $user->account_status,
                    'redirect_route' => $user->role === 'admin' ? route('admin.dashboard') : '/',
                ],
            ]);
        }

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('success', 'Welcome to the Admin Portal!');
        }

        return redirect('/')->with('success', 'Logged in successfully.');
    }

    /**
     * Logout & End Session
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Session terminated successfully.',
            ]);
        }

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }

    /**
     * Current authenticated user profile
     */
    public function me()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Not authenticated.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $user,
        ]);
    }
}
