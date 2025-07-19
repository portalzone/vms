<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Maintenance;
use App\Models\CheckInOut;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'vehicles' => Vehicle::count(),
            'drivers' => Driver::count(),
            'expenses' => Expense::sum('amount'),
            'trips' => Trip::count(),
            'maintenances' => [
                'pending'     => Maintenance::where('status', 'pending')->count(),
                'in_progress' => Maintenance::where('status', 'in_progress')->count(),
                'completed'   => Maintenance::where('status', 'Completed')->count(),
            ],
        ]);
    }

    public function recentActivity(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = strtolower($request->input('search'));
        $type = $request->input('type');
        $from = $request->input('from');
        $to = $request->input('to');

        $checkIns = CheckInOut::with(['vehicle', 'driver'])->get()->map(function ($c) {
            return [
                'type' => 'Check-In',
                'message' => ($c->vehicle?->plate_number ?? 'Unknown Vehicle') . ' checked in by ' . ($c->driver?->name ?? 'Unknown Driver'),
                'time' => $c->created_at,
            ];
        });

        $maintenances = Maintenance::with('vehicle')->get()->map(function ($m) {
            return [
                'type' => 'Maintenance',
                'message' => ($m->vehicle?->plate_number ?? 'Unknown Vehicle') . ' maintenance: ' . ($m->description ?? ''),
                'time' => $m->created_at,
            ];
        });

        $vehicles = Vehicle::get()->map(function ($v) {
            return [
                'type' => 'Vehicle Registered',
                'message' => 'Vehicle ' . $v->plate_number . ' was registered.',
                'time' => $v->created_at,
            ];
        });

        $drivers = Driver::with('user')->get()->map(function ($d) {
            return [
                'type' => 'Driver Registered',
                'message' => 'Driver ' . ($d->user?->name ?? 'Unknown') . ' was registered.',
                'time' => $d->created_at,
            ];
        });

        $expenses = Expense::with('vehicle')->get()->map(function ($e) {
            return [
                'type' => 'Expense',
                'message' => '₦' . number_format($e->amount, 2) . ' expense for ' . ($e->vehicle?->plate_number ?? 'Unknown Vehicle'),
                'time' => $e->created_at,
            ];
        });

$trips = Trip::with(['vehicle', 'driver'])->get()->map(function ($t) {
    return [
        'type' => 'Trip',
        'message' => 'Trip from ' . $t->start_location . ' to ' . $t->end_location .
            ' by ' . ($t->driver?->name ?? 'Unknown Driver') .
            ' using ' . ($t->vehicle?->plate_number ?? 'Unknown Vehicle'),
        'time' => $t->created_at,
    ];
});


        $all = collect()
            ->merge($checkIns)
            ->merge($maintenances)
            ->merge($vehicles)
            ->merge($drivers)
            ->merge($expenses)
            ->merge($trips)
            ->when($type, fn($items) => $items->filter(fn($item) => $item['type'] === $type))
            ->when($search, fn($items) => $items->filter(fn($item) =>
                str_contains(strtolower($item['message']), $search)
            ))
            ->when($from, fn($items) => $items->filter(fn($item) =>
                \Carbon\Carbon::parse($item['time'])->gte($from)
            ))
            ->when($to, fn($items) => $items->filter(fn($item) =>
                \Carbon\Carbon::parse($item['time'])->lte($to)
            ))
            ->sortByDesc('time')
            ->values();

        $total = $all->count();
        $paginated = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $paginated,
            'total' => $total,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function monthlyTrends()
    {
        $months = collect(range(0, 11))->map(function ($i) {
            return now()->subMonths($i)->format('M Y');
        })->reverse()->values();

        $data = [];

        foreach ($months as $monthLabel) {
            $month = Carbon::createFromFormat('M Y', $monthLabel);

            $vehicles = DB::table('vehicles')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $drivers = DB::table('drivers')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $expenses = DB::table('expenses')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount');

            $maintenances = DB::table('maintenances')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('cost');

            $trips = DB::table('trips')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $data[] = [
                'month'        => $monthLabel,
                'vehicles'     => $vehicles,
                'drivers'      => $drivers,
                'expenses'     => (float) $expenses,
                'maintenances' => (float) $maintenances,
                'trips'        => $trips,
            ];
        }

        return response()->json($data);
    }
}
