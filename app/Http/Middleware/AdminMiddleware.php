<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verify Authentication Session
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication required.',
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        $user = Auth::user();

        // 2. Is account role equal to 'admin'?
        if ($user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Display Access Denied: Unauthorized.',
                ], 403);
            }
            abort(403, 'Display Access Denied: Unauthorized.');
        }

        // 3. Is account_status equal to 'active'?
        if ($user->account_status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Display Account Inactive: Access Denied.',
                ], 403);
            }
            return redirect()->route('login')->with('error', 'Your admin account is inactive or suspended.');
        }

        return $next($request);
    }
}
