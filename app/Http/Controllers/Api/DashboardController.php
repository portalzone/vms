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

        $checkInsOuts = CheckInOut::with(['vehicle', 'driver.user', 'checkedInBy', 'checkedOutBy'])
            ->get()
            ->flatMap(function ($c) {
                $activities = [];

                if ($c->checked_in_at) {
                    $activities[] = [
                        'type' => 'Check-In',
                        'message' => ($c->vehicle?->manufacturer ?? 'Unknown Manufacturer'). ' ' . ($c->vehicle?->model ?? 'Unknown Model'). ' with plate number (' . ($c->vehicle?->plate_number ?? 'Unknown Plate Number') .
                                     ') was checked in by ' .
                                     ($c->checkedInBy?->name ?? 'Unknown User'),
                        'time' => $c->checked_in_at,
                    ];
                }

                if ($c->checked_out_at) {
                    $activities[] = [
                        'type' => 'Check-Out',
                        'message' => ($c->vehicle?->plate_number ?? 'Unknown Vehicle') .
                                     ' checked out by ' .
                                     ($c->checkedOutBy?->name ?? 'Unknown User'),
                        'time' => $c->checked_out_at,
                    ];
                }

                return $activities;
            });

$maintenances = Maintenance::with('vehicle')->get()->map(function ($m) {
    return [
        'type' => 'Maintenance',
        'message' => 
            ($m->vehicle?->manufacturer ?? 'Unknown Car Manufacturer') . ' ' .
            ($m->vehicle?->model ?? 'Unknown Car Model') . 
            ' with plate number (' . ($m->vehicle?->plate_number ?? 'Unknown Plate Number') . ') maintenance details: (' . 
            ($m->description ?? '') . ') cost implication is: ₦' . 
            number_format($m->cost ?? 0),
        'time' => $m->created_at,
    ];
});

$vehicles = Vehicle::with(['driver.user', 'creator', 'editor'])->get()->flatMap(function ($v) {
    $activities = [];

    // Vehicle Registered Message
    $creatorName = $v->creator?->name ?? 'Unknown User';
    $driverName = $v->driver?->user?->name ?? null;

    $driverInfo = $driverName
        ? " It is currently assigned to driver {$driverName}."
        : " It is not yet assigned to any driver.";

    $activities[] = [
        'type' => 'Vehicle Registered',
        'message' => "Vehicle {$v->manufacturer} {$v->model} (Plate: {$v->plate_number}) was registered by {$creatorName} " . Carbon::parse($v->created_at)->diffForHumans() . "." . $driverInfo,
        'time' => $v->created_at,
    ];

    // Vehicle Updated Message (only if updated after creation)
    if ($v->updated_at != $v->created_at) {
        $editorName = $v->editor?->name ?? 'Unknown User';

        $activities[] = [
            'type' => 'Vehicle Updated',
            'message' => "Vehicle {$v->manufacturer} {$v->model} (Plate: {$v->plate_number}) was last edited by {$editorName} " . Carbon::parse($v->updated_at)->diffForHumans() . "." . $driverInfo,
            'time' => $v->updated_at,
        ];
    }

    return $activities;
});


$drivers = Driver::with(['user', 'vehicle', 'creator', 'editor'])->get()->flatMap(function ($d) {
    $activities = [];

    $driverName = $d->user?->name ?? 'Unknown Driver';
    $creatorName = $d->creator?->name ?? 'Unknown User';
    $vehicleInfo = $d->vehicle
        ? " and was assigned to vehicle {$d->vehicle->manufacturer} {$d->vehicle->model} with plate number {$d->vehicle->plate_number}."
        : ".";

    // Driver Registered Message
    $activities[] = [
        'type' => 'Driver Registered',
        'message' => "Driver {$driverName} was registered by {$creatorName} " . Carbon::parse($d->created_at)->diffForHumans() . $vehicleInfo,
        'time' => $d->created_at,
    ];

    // Driver Updated Message (if updated)
    if ($d->updated_at != $d->created_at) {
        $editorName = $d->editor?->name ?? 'Unknown User';

        $activities[] = [
            'type' => 'Driver Updated',
            'message' => "Driver {$driverName} was last edited by {$editorName} " . Carbon::parse($d->updated_at)->diffForHumans() . $vehicleInfo,
            'time' => $d->updated_at,
        ];
    }

    return $activities;
});




        $expenses = Expense::with('vehicle')->get()->map(function ($e) {
            return [
                'type' => 'Expense',
                'message' => '₦' . number_format($e->amount, 2) . ' expense for ' . ($e->vehicle?->plate_number ?? 'Unknown Vehicle'),
                'time' => $e->created_at,
            ];
        });

$trips = Trip::with(['vehicle', 'driver.user'])->get()->map(function ($t) {
    $startTime = Carbon::parse($t->start_time);
    $endTime = Carbon::parse($t->end_time);

    // Calculate duration
    $duration = $startTime->diff($endTime);
    $durationString = $duration->format('%h hour(s) %i minute(s)');

    return [
        'type' => 'Trip',
        'message' => 'Trip from ' . $t->start_location . ' to ' . $t->end_location .
                     ' by ' . ($t->driver?->user?->name ?? 'Unknown Driver') .
                     ' using ' . ($t->vehicle?->manufacturer ?? 'Unknown Manufacturer') . ' ' .
                     ($t->vehicle?->model ?? 'Unknown Model') . 
                     ' (' . ($t->vehicle?->plate_number ?? 'Unknown Plate Number') . ') ' .
                     'started at ' . $startTime->format('M j, Y g:i A') . 
                     ' and ended at ' . $endTime->format('M j, Y g:i A') . 
                     ', duration: ' . $durationString,
        'time' => $t->created_at,
    ];
});

        $all = collect()
            ->merge($checkInsOuts)
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

            $vehicles = DB::table('vehicles')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
            $drivers = DB::table('drivers')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();
                        $expenses = DB::table('expenses')->whereMonth('date', $month->month)->whereYear('date', $month->year)->sum('amount');
            $maintenances = DB::table('maintenances')->whereMonth('date', $month->month)->whereYear('date', $month->year)->sum('cost');
            // $expenses = DB::table('expenses')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->sum('amount');
            // $maintenances = DB::table('maintenances')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->sum('cost');
            $trips = DB::table('trips')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();

            $data[] = [
                'month' => $monthLabel,
                'vehicles' => $vehicles,
                'drivers' => $drivers,
                'expenses' => (float) $expenses,
                'maintenances' => (float) $maintenances,
                'trips' => $trips,
            ];
        }

        return response()->json($data);
    }
}
