<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Title;
use App\Services\AdminLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TitleController extends Controller
{
    /**
     * MODULE 03 — Catalog & Submission Control
     * Page 3 — Title Submission & Management
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // pending, approved, rejected
        $type = $request->query('type');     // anime, movie, book
        $hidden = $request->query('hidden'); // 1 or 0
        $search = $request->query('search');

        $query = Title::with(['genres', 'submitter:user_id,username', 'reviewer:user_id,username']);

        if ($status) {
            $query->where('Submission_status', $status);
        }

        if ($type) {
            $query->where('title_type', $type);
        }

        if ($hidden !== null) {
            $query->where('is_hidden', (bool)$hidden);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title_name', 'like', "%{$search}%")
                  ->orWhere('alternative_title', 'like', "%{$search}%");
            });
        }

        $titles = $query->orderBy('created_date', 'desc')->paginate(15)->withQueryString();
        $genres = Genre::all();
        $pendingCount = Title::where('Submission_status', 'pending')->count();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $titles,
            ]);
        }

        return view('admin.titles.index', compact('titles', 'genres', 'status', 'type', 'hidden', 'search', 'pendingCount'));
    }

    public function pendingQueue(Request $request)
    {
        $pending = Title::with(['genres', 'submitter:user_id,username'])
            ->where('Submission_status', 'pending')
            ->orderBy('created_date', 'asc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $pending,
            ]);
        }

        return redirect()->route('admin.titles.index', ['status' => 'pending']);
    }

    public function reviewSubmission(Request $request, int $titleId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $title = Title::findOrFail($titleId);

        $title->update([
            'Submission_status' => $validated['status'],
            'Reviewed_by'       => Auth::id(),
            'Reviewed_date'     => now(),
        ]);

        $action = strtoupper($validated['status']);
        AdminLogger::log(
            "TITLE_{$action}",
            "Admin {$action} title '{$title->title_name}' (ID: {$title->title_id})"
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Title {$title->title_name} has been {$validated['status']}.",
                'data'    => $title,
            ]);
        }

        return back()->with('success', "Title '{$title->title_name}' has been {$validated['status']}.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_name'        => 'required|string|max:255',
            'alternative_title' => 'nullable|string|max:255',
            'title_type'        => 'required|in:anime,movie,book',
            'description'       => 'nullable|string',
            'release_date'      => 'nullable|date',
            'cover_image'       => 'nullable|string|max:500',
            'cover_image_file'  => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'genre_ids'         => 'nullable|array',
            'genre_ids.*'       => 'exists:genres,genre_id',
        ]);

        // Uploaded image file takes priority over the URL field
        if ($request->hasFile('cover_image_file')) {
            $file = $request->file('cover_image_file');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/titles'), $fileName);
            $validated['cover_image'] = 'uploads/titles/' . $fileName;
        }

        $title = Title::create([
            'title_name'        => $validated['title_name'],
            'alternative_title' => $validated['alternative_title'] ?? null,
            'title_type'        => $validated['title_type'],
            'description'       => $validated['description'] ?? null,
            'release_date'      => $validated['release_date'] ?? null,
            'cover_image'       => $validated['cover_image'] ?? null,
            'Submission_status' => 'approved',
            'Submitted_by'      => Auth::id(),
            'Reviewed_by'       => Auth::id(),
            'Reviewed_date'     => now(),
            'is_hidden'         => false,
        ]);

        if (!empty($validated['genre_ids'])) {
            $title->genres()->sync($validated['genre_ids']);
        }

        AdminLogger::log(
            'ADD_TITLE_MANUAL',
            "Manually created title '{$title->title_name}' (ID: {$title->title_id})"
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Title '{$title->title_name}' created.",
                'data'    => $title,
            ], 201);
        }

        return back()->with('success', "Title '{$title->title_name}' added to the catalog.");
    }

    public function hide(Request $request, int $titleId)
    {
        $title = Title::findOrFail($titleId);
        $title->update(['is_hidden' => true]);

        AdminLogger::log(
            'HIDE_TITLE',
            "Set title '{$title->title_name}' to hidden"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $title]);
        }

        return back()->with('success', "Title '{$title->title_name}' is now hidden from the public.");
    }

    public function restore(Request $request, int $titleId)
    {
        $title = Title::findOrFail($titleId);
        $title->update(['is_hidden' => false]);

        AdminLogger::log(
            'RESTORE_TITLE',
            "Restored hidden title '{$title->title_name}'"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $title]);
        }

        return back()->with('success', "Title '{$title->title_name}' is visible in the catalog again.");
    }
}
