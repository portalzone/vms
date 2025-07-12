<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;


class TripController extends Controller
{

public function index()
{
    $this->authorizeAccess('view');

    $user = auth()->user();

    if ($user->hasRole('driver')) {
        return Trip::with(['vehicle', 'driver.user']) // ✅ FIXED
            ->where('driver_id', $user->driver->id)
            ->get();
    }

    return Trip::with(['vehicle', 'driver.user']) // ✅ FIXED
        ->get();
}


public function store(Request $request)
{
$request->validate([
    'user_id'       => 'required|exists:users,id',
    'vehicle_id'    => 'required|exists:vehicles,id',
    'start_location'=> 'required|string|max:255',
    'end_location'  => 'required|string|max:255',
    'start_time'    => 'required|date',
    'end_time'      => 'nullable|date|after_or_equal:start_time',
]);

// 🔁 Find driver by user_id
$driver = \App\Models\Driver::where('user_id', $request->user_id)->first();

if (!$driver) {
    return response()->json(['message' => 'User is not assigned as a driver.'], 422);
}

// ✅ Use driver_id in the Trip
$trip = \App\Models\Trip::create([
    'driver_id'     => $driver->id,
    'vehicle_id'    => $request->vehicle_id,
    'start_location'=> $request->start_location,
    'end_location'  => $request->end_location,
    'start_time'    => $request->start_time,
    'end_time'      => $request->end_time,
]);

return response()->json($trip->load('driver.user', 'vehicle'), 201);

}

public function show($id)
{
    $trip = Trip::with(['vehicle', 'driver.user'])->findOrFail($id);

    // ✅ Add user_id to top-level trip data
    $trip->user_id = $trip->driver?->user_id;

    return response()->json($trip);
}


public function update(Request $request, $id)
{
    $trip = Trip::findOrFail($id);

    $validated = $request->validate([
        'start_location' => 'sometimes|string',
        'end_location' => 'sometimes|string',
        'start_time' => 'sometimes|date',
        'end_time' => 'nullable|date|after_or_equal:start_time',
        'notes' => 'nullable|string',
        'vehicle_id' => 'sometimes|exists:vehicles,id',
        'driver_id' => 'sometimes|exists:drivers,id',
    ]);

    $trip->update($validated);

    return response()->json($trip->load(['vehicle', 'driver.user']));
}

private function authorizeAccess(string $action): void
{
    $user = auth()->user();

    $permissions = [
        'view'   => ['admin', 'manager', 'driver'],
        'create' => ['admin', 'manager', 'driver'],
        'update' => ['admin', 'manager', 'driver'],
        'delete' => ['admin', 'manager'],
    ];

    $allowedRoles = $permissions[$action] ?? [];

    if (!$user || !$user->hasAnyRole($allowedRoles)) {
        abort(403, 'Unauthorized for this action.');
    }
}


public function destroy($id)
{
    $trip = Trip::findOrFail($id);
    $trip->delete();

    return response()->json(['message' => 'Trip deleted']);
}

}
