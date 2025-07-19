<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Driver;
use Illuminate\Http\Request;

class TripController extends Controller
{
    // ✅ Get all trips with vehicle and driver (user) details
    public function index()
    {
        $trips = Trip::with(['driver', 'vehicle'])->latest()->paginate(10);
        return response()->json($trips);
    }

    // ✅ Store a new trip
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'start_location' => 'required|string|max:255',
            'end_location'   => 'required|string|max:255',
            'start_time'     => 'required|date',
            'end_time'       => 'nullable|date|after_or_equal:start_time',
        ]);

        // ✅ Get the driver assigned to this vehicle
        $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

        if (!$driver) {
            return response()->json(['message' => 'No driver assigned to this vehicle.'], 422);
        }

        // ✅ Create the trip using the driver's user_id as driver_id
        $trip = Trip::create([
            'driver_id'      => $driver->user_id, // 👈 this stores user_id
            'vehicle_id'     => $validated['vehicle_id'],
            'start_location' => $validated['start_location'],
            'end_location'   => $validated['end_location'],
            'start_time'     => $validated['start_time'],
            'end_time'       => $validated['end_time'],
        ]);

        return response()->json([
            'message' => 'Trip created successfully.',
            'trip' => $trip->load(['driver', 'vehicle'])
        ], 201);
    }

    // ✅ Show one trip with full relationships
    public function show(Trip $trip)
    {
        $trip->load(['driver', 'vehicle']);
        return response()->json($trip);
    }

    // ✅ Update a trip
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'start_location' => 'required|string|max:255',
            'end_location'   => 'required|string|max:255',
            'start_time'     => 'required|date',
            'end_time'       => 'nullable|date|after_or_equal:start_time',
        ]);

        $trip->update($validated);

        return response()->json([
            'message' => 'Trip updated successfully.',
            'trip' => $trip->load(['driver', 'vehicle'])
        ]);
    }

    // ✅ Delete a trip
    public function destroy(Trip $trip)
    {
        $trip->delete();
        return response()->json(['message' => 'Trip deleted successfully.']);
    }
}
