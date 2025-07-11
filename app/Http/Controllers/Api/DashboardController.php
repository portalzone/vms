<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Maintenance;
use App\Models\CheckInOut;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
   public function stats()
{
    return response()->json([
        'vehicles' => \App\Models\Vehicle::count(),
        'drivers' => \App\Models\Driver::count(),
        'expenses' => \App\Models\Expense::sum('amount'),
        'maintenances' => \App\Models\Maintenance::count(),
    ]);
}


    public function recentActivity()
    {
$latestCheckIns = CheckInOut::with(['vehicle', 'driver'])->latest()->take(5)->get()->map(function ($c) {
    return [
        'type' => 'Check-In',
        'message' => ($c->vehicle?->plate_number ?? 'Unknown Vehicle') . ' checked in by ' . ($c->driver?->name ?? 'Unknown Driver'),
        'time' => optional($c->created_at)->diffForHumans(),
    ];
});

$latestMaintenances = Maintenance::with('vehicle')->latest()->take(5)->get()->map(function ($m) {
    return [
        'type' => 'Maintenance',
        'message' => ($m->vehicle?->plate_number ?? 'Unknown Vehicle') . ' maintenance: ' . ($m->description ?? ''),
        'time' => optional($m->created_at)->diffForHumans(),
    ];
});


        $activities = $latestCheckIns
            ->merge($latestMaintenances)
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return response()->json($activities);
    }

    public function monthlyTrends()
{
    $start = now()->subMonths(11)->startOfMonth();

    $months = collect(range(0, 11))->map(function ($i) use ($start) {
        return $start->copy()->addMonths($i)->format('Y-m');
    });

    // Fetch monthly data safely
    $vehicles = \App\Models\Vehicle::where('created_at', '>=', $start)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
        ->groupBy('month')
        ->pluck('total', 'month');

    $expenses = \App\Models\Expense::where('created_at', '>=', $start)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
        ->groupBy('month')
        ->pluck('total', 'month');

    $maintenances = \App\Models\Maintenance::where('created_at', '>=', $start)
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
        ->groupBy('month')
        ->pluck('total', 'month');

    // Format data for frontend chart
    $data = $months->map(function ($month) use ($vehicles, $expenses, $maintenances) {
        return [
            'month' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y'),
            'vehicles' => (int) ($vehicles[$month] ?? 0),
            'expenses' => (float) ($expenses[$month] ?? 0),
            'maintenances' => (int) ($maintenances[$month] ?? 0),
        ];
    });

    return response()->json($data);
}


}
