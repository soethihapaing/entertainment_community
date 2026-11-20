<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Title;
use App\Services\AdminLogger;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    /**
     * MODULE 04 — Taxonomy & Title Classification
     * Page 4 — Genre Management
     */
    public function index(Request $request)
    {
        $genres = Genre::withCount('titles')->orderBy('genre_name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $genres,
            ]);
        }

        return view('admin.genres.index', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'genre_name'  => 'required|string|max:100|unique:genres,genre_name',
            'description' => 'nullable|string',
        ]);

        $genre = Genre::create($validated);

        AdminLogger::log(
            'ADD_GENRE',
            "Created new genre '{$genre->genre_name}' (ID: {$genre->genre_id})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $genre], 201);
        }

        return back()->with('success', "Genre '{$genre->genre_name}' created successfully.");
    }

    public function update(Request $request, int $genreId)
    {
        $genre = Genre::findOrFail($genreId);

        $validated = $request->validate([
            'genre_name'  => "required|string|max:100|unique:genres,genre_name,{$genreId},genre_id",
            'description' => 'nullable|string',
        ]);

        $genre->update($validated);

        AdminLogger::log(
            'EDIT_GENRE',
            "Updated genre '{$genre->genre_name}' (ID: {$genre->genre_id})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $genre]);
        }

        return back()->with('success', "Genre '{$genre->genre_name}' updated successfully.");
    }

    public function destroy(Request $request, int $genreId)
    {
        $genre = Genre::findOrFail($genreId);
        $name = $genre->genre_name;

        $genre->titles()->detach();
        $genre->delete();

        AdminLogger::log(
            'DELETE_GENRE',
            "Deleted genre '{$name}' (ID: {$genreId})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "Genre '{$name}' deleted."]);
        }

        return back()->with('success', "Genre '{$name}' deleted successfully.");
    }

    public function assignToTitle(Request $request, int $titleId)
    {
        $title = Title::findOrFail($titleId);

        $validated = $request->validate([
            'genre_ids'   => 'required|array',
            'genre_ids.*' => 'exists:genres,genre_id',
        ]);

        $title->genres()->syncWithoutDetaching($validated['genre_ids']);

        AdminLogger::log(
            'ASSIGN_GENRE',
            "Assigned genres to title '{$title->title_name}'"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $title->load('genres')]);
        }

        return back()->with('success', "Genres assigned to the title.");
    }

    public function removeFromTitle(Request $request, int $titleId, int $genreId)
    {
        $title = Title::findOrFail($titleId);
        $genre = Genre::findOrFail($genreId);

        $title->genres()->detach($genreId);

        AdminLogger::log(
            'REMOVE_GENRE',
            "Removed genre '{$genre->genre_name}' from title '{$title->title_name}'"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $title->load('genres')]);
        }

        return back()->with('success', "Genre removed from the title.");
    }
}
