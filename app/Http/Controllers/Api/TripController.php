<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Income;


class TripController extends Controller
{
    // ✅ List trips (Admin/Manager can see all, Driver sees only theirs)

    public function index(Request $request)
{
    $this->authorizeAccess('view');
    $user = Auth::user();
    $query = Trip::with(['vehicle', 'driver.user']);

    if ($user->hasRole('driver')) {
        $driver = Driver::where('user_id', $user->id)->first();
        if ($driver) {
            $query->where('driver_id', $driver->id);
        } else {
            return response()->json([], 200);
        }
    } elseif ($user->hasRole('vehicle_owner')) {
        // Assuming vehicles have user_id pointing to owner
        $query->whereHas('vehicle', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        });
    } elseif ($user->hasRole('admin') || $user->hasRole('manager')) {
        // No filter needed
    } else {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Optional: filter by status (e.g., ?status=completed)
    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    // ✅ Respect per_page parameter
    $perPage = $request->input('per_page', 10);

    if ($perPage === 'all') {        // Return all without pagination
        return response()->json($query->orderBy('status')->latest()->get());
    }

    return $query->orderBy('status')->latest()->paginate((int) $perPage);
}

// public function index(Request $request)
// {
//             $this->authorizeAccess('view');
//     $user = Auth::user();
//     $query = Trip::with(['vehicle', 'driver.user']);

//     if ($user->hasRole('driver')) {
//         $driver = Driver::where('user_id', $user->id)->first();
//         if ($driver) {
//             $query->where('driver_id', $driver->id);
//         } else {
//             return response()->json([], 200);
//         }
//     } elseif ($user->hasRole('vehicle_owner')) {
//         // Assuming vehicles have user_id pointing to owner
//         $query->whereHas('vehicle', function ($q) use ($user) {
//             $q->where('owner_id', $user->id);
//         });
//     } elseif ($user->hasRole('admin') || $user->hasRole('manager')) {
//         // No filter needed
//     } else {
//         // Default: deny access
//         return response()->json(['error' => 'Unauthorized'], 403);
//     }

//     // Optional: filter by status (e.g., ?status=completed)
//     if ($request->has('status')) {
//         $query->where('status', $request->status);
//     }

//     return $query->orderBy('status')->latest()->paginate(10);
// }
// ✅ Return all trips (for dropdowns etc)
public function all()
{
    $this->authorizeAccess('view');
    $trips = Trip::with(['vehicle', 'driver.user'])->latest()->get();
    return response()->json($trips);
}


    // ✅ Store a new trip
public function store(Request $request)
{
    $this->authorizeAccess('create');
    $validated = $request->validate([
        'vehicle_id'     => ['required', Rule::exists('vehicles', 'id')],
        'start_location' => ['required', 'string'],
        'end_location'   => ['required', 'string'],
        'amount' => ['nullable', 'numeric', 'min:0'],
        'start_time'     => ['required', 'date'],
        'end_time'       => ['nullable', 'date', 'after_or_equal:start_time'],
    ]);

    $startTime = Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
    $endTime   = isset($validated['end_time']) ? Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s') : null;

    $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

    if (!$driver) {
        return response()->json(['error' => 'Driver not assigned to vehicle'], 422);
    }

    $trip = Trip::create([
        'driver_id'      => $driver->id,
        'vehicle_id'     => $validated['vehicle_id'],
        'start_location' => $validated['start_location'],
        'end_location'   => $validated['end_location'],
        'amount'         => $validated['amount'] ?? null,
        'start_time'     => $startTime,
        'end_time'       => $endTime,
        'status'         => $endTime ? 'completed' : 'in_progress',
    ]);

$this->createIncomeForCompletedTrip($trip);
return response()->json($trip->load(['driver.user', 'vehicle']), 201);

}




public function update(Request $request, Trip $trip)
{
    $this->authorizeAccess('update');
    $validated = $request->validate([
        'vehicle_id'     => ['required', Rule::exists('vehicles', 'id')],
        'start_location' => ['required', 'string'],
        'end_location'   => ['required', 'string'],
        'amount' => ['nullable', 'numeric', 'min:0'],
        'start_time'     => ['required', 'date'],
        'end_time'       => ['nullable', 'date', 'after_or_equal:start_time'],
    ]);

    $startTime = Carbon::parse($validated['start_time'])->format('Y-m-d H:i:s');
    $endTime   = isset($validated['end_time']) ? Carbon::parse($validated['end_time'])->format('Y-m-d H:i:s') : null;

    $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

    if (!$driver) {
        return response()->json(['error' => 'Driver not assigned to vehicle'], 422);
    }

    $trip->update([
        'driver_id'      => $driver->id,
        'vehicle_id'     => $validated['vehicle_id'],
        'start_location' => $validated['start_location'],
        'end_location'   => $validated['end_location'],
        'amount'         => $validated['amount'] ?? null,
        'start_time'     => $startTime,
        'end_time'       => $endTime,
        'status'         => $endTime ? 'completed' : 'in_progress',
    ]);

    $this->createIncomeForCompletedTrip($trip);


    return response()->json($trip->load(['driver.user', 'vehicle']));
}

// create income for completed trip:
private function createIncomeForCompletedTrip(Trip $trip)
{
    if ($trip->status !== 'completed') {
        return;
    }

    $existingIncome = Income::where('trip_id', $trip->id)->first();

    if ($existingIncome) {
        // Update existing income
        $existingIncome->update([
            'amount'      => $trip->amount ?? 0,
            'description' => "Auto-updated income for trip from {$trip->start_location} to {$trip->end_location}",
            'vehicle_id'  => $trip->vehicle_id,
            'driver_id'   => $trip->driver_id,
            'date'        => now(), // or keep original date if preferred
        ]);
    } else {
        // Create new income
        Income::create([
            'trip_id'     => $trip->id,
            'vehicle_id'  => $trip->vehicle_id,
            'driver_id'   => $trip->driver_id,
            'source'      => 'Trip Completed',
            'amount'      => $trip->amount ?? 0,
            'description' => "Auto-generated income for trip from {$trip->start_location} to {$trip->end_location}",
            'date'        => now(),
        ]);
    }
}




    // ✅ Show a single trip
    public function show(Trip $trip)
    {
        $this->authorizeAccess('view');
        $trip->load(['vehicle', 'driver.user']);
        return response()->json($trip);
    }

 

    // ✅ Delete a trip
    public function destroy(Trip $trip)
    {
        $this->authorizeAccess('delete');
        $trip->delete();
        return response()->json(['message' => 'Trip deleted successfully']);
    }

    
    /**
     * 🔐 Role-based permission checker
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $rolePermissions = [
            'view'   => ['admin', 'manager', 'vehicle_owner', 'driver'],
            'create' => ['admin', 'manager', 'driver'],
            'update' => ['admin', 'manager', 'driver'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $rolePermissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
             \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
            abort(403, 'Unauthorized for this action.');
        }
    }
}
