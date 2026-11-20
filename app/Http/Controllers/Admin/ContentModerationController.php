<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscussionComment;
use App\Models\DiscussionTopic;
use App\Models\Review;
use App\Services\AdminLogger;
use Illuminate\Http\Request;

class ContentModerationController extends Controller
{
    /**
     * MODULE 07 — Soft Deletion & Content Recovery
     * Page 7 — Content Moderation & Restoration
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'reviews'); // reviews, topics, comments
        $onlyModerated = $request->boolean('only_moderated', false);

        $data = match ($type) {
            'reviews' => Review::with(['user:user_id,username', 'title:title_id,title_name', 'deletedByUser:user_id,username'])
                ->when($onlyModerated, fn($q) => $q->where('is_deleted', 1))
                ->orderBy('created_date', 'desc')
                ->paginate(15)->withQueryString(),

            'topics' => DiscussionTopic::with(['user:user_id,username', 'title:title_id,title_name', 'category:category_id,category_name', 'deletedByUser:user_id,username'])
                ->when($onlyModerated, fn($q) => $q->where('is_deleted', 1))
                ->orderBy('created_date', 'desc')
                ->paginate(15)->withQueryString(),

            'comments' => DiscussionComment::with(['user:user_id,username', 'topic:topic_id,title', 'deletedByUser:user_id,username'])
                ->when($onlyModerated, fn($q) => $q->where('is_deleted', 1))
                ->orderBy('created_date', 'desc')
                ->paginate(15)->withQueryString(),

            default => null,
        };

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'type'   => $type,
                'data'   => $data,
            ]);
        }

        return view('admin.moderation.index', compact('data', 'type', 'onlyModerated'));
    }

    public function hideContent(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:review,topic,comment',
            'id'   => 'required|integer',
        ]);

        $type = $validated['type'];
        $id = $validated['id'];
        $adminId = auth()->id();

        match ($type) {
            'review' => Review::where('review_id', $id)->update([
                'is_deleted'   => true,
                'deleted_by'   => $adminId,
                'deleted_date' => now(),
            ]),
            'topic' => DiscussionTopic::where('topic_id', $id)->update([
                'is_deleted'   => true,
                'deleted_by'   => $adminId,
                'deleted_date' => now(),
            ]),
            'comment' => DiscussionComment::where('comment_id', $id)->update([
                'is_deleted'   => true,
                'deleted_by'   => $adminId,
                'deleted_date' => now(),
            ]),
        };

        AdminLogger::log(
            'CONTENT_SOFT_DELETE',
            "Soft-deleted {$type} #{$id}"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "Successfully soft-deleted {$type} #{$id}."]);
        }

        return back()->with('success', "{$type} #{$id} has been soft-deleted and hidden from the public.");
    }

    public function restoreContent(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:review,topic,comment',
            'id'   => 'required|integer',
        ]);

        $type = $validated['type'];
        $id = $validated['id'];

        match ($type) {
            'review' => Review::where('review_id', $id)->update([
                'is_deleted'   => false,
                'deleted_by'   => null,
                'deleted_date' => null,
            ]),
            'topic' => DiscussionTopic::where('topic_id', $id)->update([
                'is_deleted'   => false,
                'deleted_by'   => null,
                'deleted_date' => null,
            ]),
            'comment' => DiscussionComment::where('comment_id', $id)->update([
                'is_deleted'   => false,
                'deleted_by'   => null,
                'deleted_date' => null,
            ]),
        };

        AdminLogger::log(
            'CONTENT_RESTORE',
            "Restored {$type} #{$id} to active state"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => "Successfully restored {$type} #{$id}."]);
        }

        return back()->with('success', "{$type} #{$id} has been restored.");
    }
}
