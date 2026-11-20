<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * MODULE 08 — Accountability & Audit Trail
     * Page 8 — Admin Activity Logs
     */
    public function index(Request $request)
    {
        $adminId = $request->query('admin_id');
        $activityType = $request->query('activity_type');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = AdminActivityLog::with('admin:user_id,username,full_name,email');

        if ($adminId) {
            $query->where('admin_id', $adminId);
        }

        if ($activityType) {
            $query->where('activity_type', $activityType);
        }

        if ($fromDate) {
            $query->whereDate('created_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_date', '<=', $toDate);
        }

        $logs = $query->orderBy('created_date', 'desc')->paginate(20)->withQueryString();
        $admins = User::where('role', 'admin')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $logs,
            ]);
        }

        return view('admin.logs.index', compact('logs', 'admins', 'adminId', 'activityType', 'fromDate', 'toDate'));
    }

    public function show(Request $request, int $logId)
    {
        $log = AdminActivityLog::with('admin:user_id,username,full_name,email')->findOrFail($logId);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $log,
            ]);
        }

        return response()->json(['status' => 'success', 'data' => $log]);
    }
}
