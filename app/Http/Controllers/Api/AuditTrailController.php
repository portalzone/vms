<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess('view');

        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('log_name') && $request->log_name !== 'all') {
            $query->where('log_name', $request->log_name);
        }

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

        return response()->json($query->paginate(10));
    }

    protected function applyCustomDateRange($query, Request $request): void
    {
        if ($request->filled(['start_date', 'end_date'])) {
            try {
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            } catch (\Exception $e) {
                // Optionally handle invalid dates
            }
        }
    }

    public function show($id)
    {
        $this->authorizeAccess('view');

        $log = Activity::with('causer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Audit log retrieved successfully.',
            'data' => $log,
        ]);
    }

    /**
     * 🔐 Role-based access control
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $roles = [
            'view' => ['admin', 'manager'],
        ];

        if (!$user || !$user->hasAnyRole($roles[$action] ?? [])) {
             \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
            abort(403, 'Unauthorized for this action.');
        }
    }
}
