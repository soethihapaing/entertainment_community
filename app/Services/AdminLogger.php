<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Auth;

class AdminLogger
{
    /**
     * Log an administrative action to admin_activity_logs table
     *
     * @param string $activityType
     * @param string|null $description
     * @param int|null $adminId
     * @return AdminActivityLog
     */
    public static function log(string $activityType, ?string $description = null, ?int $adminId = null): AdminActivityLog
    {
        $adminId = $adminId ?? (Auth::id() ?? 1); // fallback to ID 1 if running from CLI/Seeder

        return AdminActivityLog::create([
            'admin_id'      => $adminId,
            'activity_type' => $activityType,
            'description'   => $description,
            'created_date'  => now(),
        ]);
    }
}
