<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditTrailController extends Controller
{
    // ✅ Get all audit logs with optional filters
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        // 🔍 Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%");
            });
        }

        // 📦 Filter by module (log_name)
        if ($request->filled('log_name') && $request->log_name !== 'all') {
            $query->where('log_name', $request->log_name);
        }

        // 📅 Filter by time range
        if ($request->filled('time_range')) {
            $now = now();

            match ($request->time_range) {
                '24h' => $query->where('created_at', '>=', $now->subDay()),
                '7d'  => $query->where('created_at', '>=', $now->subDays(7)),
                '30d' => $query->where('created_at', '>=', $now->subDays(30)),
                'custom' => $this->applyCustomDateRange($query, $request),
                default => null,
            };
        }

        // ✅ Return paginated filtered results
        return response()->json($query->paginate(10));
    }

    // 🔧 Handle custom date range filtering
    protected function applyCustomDateRange($query, Request $request): void
    {
        if ($request->filled(['start_date', 'end_date'])) {
            try {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();

                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                // Optional: log or throw a validation error
            }
        }
    }

    // ✅ Get a specific audit log entry
    public function show($id)
    {
        $log = Activity::with('causer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Audit log retrieved successfully.',
            'data' => $log,
        ]);
    }
}
