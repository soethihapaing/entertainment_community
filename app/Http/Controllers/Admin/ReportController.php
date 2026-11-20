<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscussionComment;
use App\Models\DiscussionTopic;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Services\AdminLogger;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * MODULE 06 — Trust, Safety & Escalation
     * Page 6 — Report Management
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // pending, under_review, resolved, rejected
        $targetType = $request->query('target_type');

        $query = Report::with('reporter:user_id,username,email');

        if ($status) {
            $query->where('report_status', $status);
        }

        if ($targetType) {
            $query->where('target_type', $targetType);
        }

        $reports = $query->orderBy('created_date', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $reports,
            ]);
        }

        return view('admin.reports.index', compact('reports', 'status', 'targetType'));
    }

    public function show(Request $request, int $reportId)
    {
        $report = Report::with('reporter:user_id,username,email')->findOrFail($reportId);

        // Flowchart: Transition to 'under_review' if 'pending'
        if ($report->report_status === 'pending') {
            $report->update(['report_status' => 'under_review']);
        }

        $targetEntity = $report->target_entity;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'report'        => $report,
                    'target_entity' => $targetEntity,
                ],
            ]);
        }

        return view('admin.reports.show', compact('report', 'targetEntity'));
    }

    public function reject(Request $request, int $reportId)
    {
        $report = Report::findOrFail($reportId);
        $report->update(['report_status' => 'rejected']);

        AdminLogger::log(
            'REPORT_REJECTED',
            "Rejected report ID {$report->report_id} against {$report->target_type} #{$report->target_id} (No violation found)"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $report]);
        }

        return back()->with('success', "Report #{$report->report_id} rejected (no violation found).");
    }

    public function resolve(Request $request, int $reportId)
    {
        $validated = $request->validate([
            'action' => 'required|in:soft_delete_content,suspend_user,none',
        ]);

        $report = Report::findOrFail($reportId);
        $actionTaken = $validated['action'];
        $adminId = auth()->id();

        if ($actionTaken === 'soft_delete_content') {
            match ($report->target_type) {
                'review' => Review::where('review_id', $report->target_id)->update([
                    'is_deleted'   => true,
                    'deleted_by'   => $adminId,
                    'deleted_date' => now(),
                ]),
                'discussion_topic' => DiscussionTopic::where('topic_id', $report->target_id)->update([
                    'is_deleted'   => true,
                    'deleted_by'   => $adminId,
                    'deleted_date' => now(),
                ]),
                'discussion_comment' => DiscussionComment::where('comment_id', $report->target_id)->update([
                    'is_deleted'   => true,
                    'deleted_by'   => $adminId,
                    'deleted_date' => now(),
                ]),
                default => null,
            };

            AdminLogger::log(
                'REPORT_ACTION_SOFT_DELETE',
                "Soft-deleted violating {$report->target_type} #{$report->target_id} from report #{$report->report_id}"
            );
        } elseif ($actionTaken === 'suspend_user') {
            $targetUserId = ($report->target_type === 'user')
                ? $report->target_id
                : ($report->target_entity->user_id ?? null);

            if ($targetUserId) {
                User::where('user_id', $targetUserId)->update([
                    'account_status' => 'suspended',
                    'updated_date'   => now(),
                ]);

                AdminLogger::log(
                    'REPORT_ACTION_SUSPEND_USER',
                    "Suspended user #{$targetUserId} in resolution of report #{$report->report_id}"
                );
            }
        }

        $report->update(['report_status' => 'resolved']);

        AdminLogger::log(
            'REPORT_RESOLVED',
            "Resolved report #{$report->report_id} with action: {$actionTaken}"
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'data' => $report]);
        }

        return back()->with('success', "Report #{$report->report_id} actioned and marked as resolved.");
    }
}
