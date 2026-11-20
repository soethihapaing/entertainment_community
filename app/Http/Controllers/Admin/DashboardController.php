<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\DiscussionTopic;
use App\Models\Report;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * MODULE 01 — System Access & Overview
     * Page 1 — Admin Dashboard & Statistics
     */
    public function index(Request $request)
    {
        // 1. User metrics
        $users = [
            'total'       => User::count(),
            'active'      => User::where('account_status', 'active')->count(),
            'suspended'   => User::where('account_status', 'suspended')->count(),
            'deactivated' => User::where('account_status', 'deactivated')->count(),
            'new_today'   => User::whereDate('created_date', today())->count(),
        ];

        // 2. Title metrics
        $titles = [
            'total'     => Title::count(),
            'pending'   => Title::where('Submission_status', 'pending')->count(),
            'approved'  => Title::where('Submission_status', 'approved')->count(),
            'rejected'  => Title::where('Submission_status', 'rejected')->count(),
            'hidden'    => Title::where('is_hidden', 1)->count(),
        ];

        // 3. Reviews & Discussions (Active vs Moderated/Soft-deleted)
        $content = [
            'reviews_active'      => Review::where('is_deleted', 0)->count(),
            'reviews_moderated'   => Review::where('is_deleted', 1)->count(),
            'topics_active'       => DiscussionTopic::where('is_deleted', 0)->count(),
            'topics_moderated'    => DiscussionTopic::where('is_deleted', 1)->count(),
        ];

        // 4. Reports metrics
        $reports = [
            'pending'      => Report::where('report_status', 'pending')->count(),
            'under_review' => Report::where('report_status', 'under_review')->count(),
            'resolved'     => Report::where('report_status', 'resolved')->count(),
            'rejected'     => Report::where('report_status', 'rejected')->count(),
        ];

        // 5. Recent audit logs
        $recent_logs = AdminActivityLog::with('admin:user_id,username,full_name')
            ->orderBy('created_date', 'desc')
            ->limit(10)
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'users'         => $users,
                    'titles'        => $titles,
                    'content'       => $content,
                    'reports'       => $reports,
                    'recent_logs'   => $recent_logs,
                ],
            ]);
        }

        return view('admin.dashboard', compact('users', 'titles', 'content', 'reports', 'recent_logs'));
    }
}
