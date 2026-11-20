<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminLogger;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * MODULE 02 — Account Administration
     * Page 2 — User Management
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = User::select([
            'user_id',
            'full_name',
            'username',
            'email',
            'role',
            'account_status',
            'created_date',
            'updated_date',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_date', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $users,
            ]);
        }

        return view('admin.users.index', compact('users', 'search'));
    }

    public function show(Request $request, int $userId)
    {
        $user = User::select([
            'user_id',
            'full_name',
            'username',
            'email',
            'role',
            'account_status',
            'created_date',
            'updated_date',
        ])
        ->withCount(['reviews', 'topics', 'comments', 'watchlist'])
        ->findOrFail($userId);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $user,
            ]);
        }

        return view('admin.users.show', compact('user'));
    }

    public function suspend(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->user_id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Cannot suspend yourself.'], 400);
            }
            return back()->with('error', 'You cannot suspend your own account.');
        }

        $user->update([
            'account_status' => 'suspended',
            'updated_date'   => now(),
        ]);

        AdminLogger::log(
            'SUSPEND_USER',
            "Suspended user account: {$user->username} (ID: {$user->user_id})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "User {$user->username} suspended.", 'data' => $user]);
        }

        return back()->with('success', "User '{$user->username}' has been suspended.");
    }

    public function reactivate(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $user->update([
            'account_status' => 'active',
            'updated_date'   => now(),
        ]);

        AdminLogger::log(
            'REACTIVATE_USER',
            "Reactivated user account: {$user->username} (ID: {$user->user_id})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "User {$user->username} reactivated.", 'data' => $user]);
        }

        return back()->with('success', "User '{$user->username}' has been reactivated.");
    }
}
