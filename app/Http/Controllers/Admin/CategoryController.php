<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AdminLogger;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * MODULE 05 — Community Forum Structure
     * Page 5 — Discussion Category Management
     */
    public function index(Request $request)
    {
        $categories = Category::withCount('topics')->orderBy('category_name')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $categories,
            ]);
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:100|unique:categories,category_name',
            'category_type' => 'required|string|max:50',
            'description'   => 'nullable|string',
        ]);

        $category = Category::create([
            'category_name' => $validated['category_name'],
            'category_type' => $validated['category_type'],
            'description'   => $validated['description'] ?? null,
            'created_by'    => auth()->id(),
        ]);

        AdminLogger::log(
            'ADD_CATEGORY',
            "Created forum category '{$category->category_name}' (ID: {$category->category_id})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $category], 201);
        }

        return back()->with('success', "Discussion category '{$category->category_name}' created successfully.");
    }

    public function destroy(Request $request, int $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        // Flowchart Safe Delete: SELECT COUNT(*) FROM discussion_topics WHERE category_id = target
        $topicsCount = $category->topics()->count();

        if ($topicsCount > 0) {
            $msg = "Category '{$category->category_name}' cannot be deleted because {$topicsCount} topic(s) are using it.";
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $name = $category->category_name;
        $category->delete();

        AdminLogger::log(
            'DELETE_CATEGORY',
            "Deleted discussion category '{$name}' (ID: {$categoryId})"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "Category '{$name}' deleted."]);
        }

        return back()->with('success', "Category '{$name}' deleted successfully.");
    }
}
