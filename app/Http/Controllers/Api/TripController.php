<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripController extends Controller
{
    // ✅ List trips (Admin/Manager can see all, Driver sees only theirs)
    public function index(Request $request)
    {
        $query = Trip::with(['vehicle', 'driver.user']);

        if (Auth::user()->hasRole('Driver')) {
            $driver = Driver::where('user_id', Auth::id())->first();
            if ($driver) {
                $query->where('driver_id', $driver->id);
            } else {
                return response()->json([], 200);
            }
        }

        return $query->latest()->paginate(10);
    }

    // ✅ Store a new trip
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'start_location' => 'required|string',
            'end_location'   => 'required|string',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date|after_or_equal:start_time',
        ]);

        // Get the driver assigned to the selected vehicle
        $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

        if (!$driver) {
            return response()->json(['error' => 'Driver not assigned to vehicle'], 422);
        }

        // ✅ Create trip using the correct driver ID
        $trip = Trip::create([
            'driver_id'      => $driver->id, // ⬅️ use driver's ID (not user_id)
            'vehicle_id'     => $validated['vehicle_id'],
            'start_location' => $validated['start_location'],
            'end_location'   => $validated['end_location'],
            'start_time'     => $validated['start_time'],
            'end_time'       => $validated['end_time'],
        ]);

        return response()->json($trip->load(['driver.user', 'vehicle']), 201);
    }

    // ✅ Show a single trip
    public function show(Trip $trip)
    {
        $trip->load(['vehicle', 'driver.user']);
        return response()->json($trip);
    }

    // ✅ Update an existing trip
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'start_location' => 'required|string',
            'end_location'   => 'required|string',
            'start_time'     => 'required|date',
            'end_time'       => 'required|date|after_or_equal:start_time',
        ]);

        $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

        if (!$driver) {
            return response()->json(['error' => 'Driver not assigned to vehicle'], 422);
        }

        $trip->update([
            'driver_id'      => $driver->id,
            'vehicle_id'     => $validated['vehicle_id'],
            'start_location' => $validated['start_location'],
            'end_location'   => $validated['end_location'],
            'start_time'     => $validated['start_time'],
            'end_time'       => $validated['end_time'],
        ]);

        return response()->json($trip->load(['driver.user', 'vehicle']));
    }

    // ✅ Delete a trip
    public function destroy(Trip $trip)
    {
        $trip->delete();
        return response()->json(['message' => 'Trip deleted successfully']);
    }
}
