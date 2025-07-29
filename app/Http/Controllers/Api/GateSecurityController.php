<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInOut;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GateSecurityController extends Controller
{
    // GET /api/gate-security/stats
    public function stats()
    {
        $today = Carbon::today();

        return response()->json([
            'vehicles_checked_in_today' => CheckInOut::whereDate('checked_in_at', $today)->count(),
            'vehicles_checked_out_today' => CheckInOut::whereDate('checked_out_at', $today)->count(),
            'active_trips' => Trip::whereNull('end_time')->count(),
        ]);
    }

    // GET /api/gate-security/recent-logs
    public function recentLogs()
    {
        $logs = CheckInOut::with([
                'vehicle:id,manufacturer,plate_number',
                'driver.user:id,name'
            ])
            ->latest()
            ->take(10)
            ->get();

        return response()->json($logs);
    }
}
