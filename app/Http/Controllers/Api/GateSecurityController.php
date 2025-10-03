<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInOut;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GateSecurityController extends Controller
{
    /**
     * GET /api/gate-security/stats
     * Returns dashboard statistics for today
     */
    public function stats()
    {
        $today = Carbon::today();

        return response()->json([
            'vehicles_checked_in_today' => CheckInOut::whereDate('checked_in_at', $today)->count(),
            'vehicles_checked_out_today' => CheckInOut::whereDate('checked_out_at', $today)
                ->whereNotNull('checked_out_at')
                ->count(),
            'active_trips' => Trip::where('status', 'ongoing')
                ->orWhereNull('end_time')
                ->count(),
            'vehicles_inside' => CheckInOut::whereNotNull('checked_in_at')
                ->whereNull('checked_out_at')
                ->count(),
        ]);
    }

    /**
     * GET /api/gate-security/recent-logs
     * Returns recent check-in/out logs with optional limit
     */
    public function recentLogs(Request $request)
    {
        $limit = $request->get('limit', 10);

        $logs = CheckInOut::with([
                'vehicle:id,manufacturer,model,plate_number',
                'driver.user:id,name'
            ])
            ->latest('checked_in_at')
            ->take($limit)
            ->get();

        return response()->json($logs);
    }

    /**
     * GET /api/gate-security/vehicles-within-premises
     * Returns all vehicles currently inside the premises
     */
    public function vehiclesWithinPremises()
    {
        $vehicles = CheckInOut::with([
                'vehicle:id,manufacturer,model,plate_number',
                'driver.user:id,name'
            ])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->orderBy('checked_in_at', 'desc')
            ->get();

        return response()->json($vehicles);
    }

    /**
     * GET /api/gate-security/alerts
     * Returns vehicles that have been inside for too long
     */
    public function alerts()
    {
        $threshold = Carbon::now()->subHours(8); // 8 hours threshold

        $longStayVehicles = CheckInOut::with([
                'vehicle:id,manufacturer,model,plate_number',
                'driver.user:id,name'
            ])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->where('checked_in_at', '<', $threshold)
            ->orderBy('checked_in_at', 'asc')
            ->get();

        return response()->json($longStayVehicles);
    }
}
