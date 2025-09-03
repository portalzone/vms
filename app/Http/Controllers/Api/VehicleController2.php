<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\CheckInOut;
use Illuminate\Http\Request;
use App\Models\Driver;




class VehicleController extends Controller
{
    // ✅ List all vehicles with driver info
public function index()
{
    $user = auth()->user();

    if (!$user || !$user->hasAnyRole(['admin', 'manager', 'vehicle_owner', 'driver', 'gate_security'])) {
        \Log::warning("Unauthorized vehicle index access attempt by user ID {$user?->id}");
        return response()->json(['error' => 'Unauthorized.'], 403);
    }

    // Vehicle Owner: only their vehicles
    if ($user->hasRole('vehicle_owner')) {
        $vehicles = Vehicle::with(['driver.user', 'creator', 'editor', 'owner'])
            ->where('owner_id', $user->id)
            ->where('ownership_type', 'individual')
            ->get();
    }

    // Driver: only their assigned vehicle
    elseif ($user->hasRole('driver')) {
        $driver = \App\Models\Driver::where('user_id', $user->id)->first();

        if (!$driver || !$driver->vehicle_id) {
            return response()->json(['message' => 'No vehicle assigned.'], 200);
        }

        $vehicles = Vehicle::with(['driver.user', 'creator', 'editor', 'owner'])
            ->where('id', $driver->vehicle_id)
            ->get();
    }

    // Admins and Managers: all vehicles
    else {
        $vehicles = Vehicle::with(['driver.user', 'creator', 'editor', 'owner'])->get();
    }

    return response()->json($vehicles);
}


public function vehiclesWithinPremises()
{
    $vehicles = CheckInOut::with('vehicle', 'driver.user')
        ->whereNull('checked_out_at')
        ->latest()
        ->get();

    return response()->json($vehicles);
}


public function assignedVehicles()
{
    $vehicles = Driver::with('vehicle')
        ->whereNotNull('vehicle_id')
        ->get()
        ->pluck('vehicle')
        ->filter()
        ->values();

    return response()->json($vehicles);
}

    // ✅ Return only vehicles that have an assigned driver (for Check-In)
    public function vehiclesWithDrivers()
    {
        $this->authorizeAccess('view');

        return Vehicle::whereHas('driver')->with('driver')->get();
    }
// vehicle with drivers
    public function withDrivers()
{
    $this->authorizeAccess('view');

    $vehicles = \App\Models\Vehicle::with(['driver.user'])
        ->whereHas('driver')
        ->get();

    return response()->json($vehicles);
}

    // ✅ Create a new vehicle
public function store(Request $request)
{
    $this->authorizeAccess('create');

    $user = auth()->user();

    $validated = $request->validate([
        'manufacturer'    => 'required|string|max:255',
        'model'           => 'required|string|max:255',
        'year'            => 'required|integer|min:1900|max:' . (date('Y') + 1),
        'plate_number'    => 'required|string|max:20|unique:vehicles',
        'ownership_type'  => 'nullable|in:organization,individual',
        'owner_id'        => 'nullable|exists:users,id',
    ]);

    // If the user is a vehicle_owner, force ownership to them
 if ($user->hasRole('vehicle_owner')) {
    // Force ownership to the logged-in vehicle owner
    $validated['ownership_type'] = 'individual';
    $validated['owner_id'] = $user->id;
} else {
    // For admin or manager: use submitted owner if individual is selected
    if ($validated['ownership_type'] === 'individual') {
        // owner_id already validated (nullable|exists)
        if (!$validated['owner_id']) {
            return response()->json(['error' => 'Please select a vehicle owner.'], 422);
        }
    } else {
        // Organization vehicle – clear owner_id
        $validated['owner_id'] = null;
    }
}


    $validated['created_by'] = $user->id;
    $validated['updated_by'] = $user->id;

    $vehicle = Vehicle::create($validated);

    return response()->json($vehicle->load(['owner', 'creator', 'editor', 'owner']), 201);
}

public function myVehicles()
{
    $user = auth()->user();

    // Make sure this user is a vehicle owner
    if (!$user || !$user->hasRole('vehicle_owner')) {
        return response()->json(['error' => 'Only vehicle owners can access this.'], 403);
    }

    // Fetch vehicles owned by this user
    $vehicles = Vehicle::with(['driver', 'creator', 'editor', 'owner'])
        ->where('owner_id', $user->id)
        ->where('ownership_type', 'individual')
        ->latest()
        ->get();

    return response()->json($vehicles);
}




    // ✅ Show a specific vehicle with driver
    public function show($id)
    {
        $this->authorizeAccess('view');
        
        return Vehicle::with(['driver', 'creator', 'editor', 'owner'])->findOrFail($id);
    }

    // ✅ Update a vehicle
public function update(Request $request, $id)
{
    $this->authorizeAccess('update');

    $vehicle = Vehicle::findOrFail($id);
    $user = auth()->user();

    $validated = $request->validate([
        'manufacturer'    => 'sometimes|required|string|max:255',
        'model'           => 'sometimes|required|string|max:255',
        'year'            => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
        'plate_number'    => 'sometimes|required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
        'ownership_type'  => 'nullable|in:organization,individual',
        'owner_id'        => 'nullable|exists:users,id',
    ]);

    // Role-specific logic
    if ($user->hasRole('vehicle_owner')) {
        // Vehicle owners can only update their own vehicles
        if ($vehicle->owner_id !== $user->id) {
            return response()->json(['error' => 'You can only update your own vehicles.'], 403);
        }

        // Force individual ownership to self
        $validated['ownership_type'] = 'individual';
        $validated['owner_id'] = $user->id;
    } else {
        // Admins/managers logic
        if (isset($validated['ownership_type']) && $validated['ownership_type'] === 'individual') {
            if (empty($validated['owner_id'])) {
                return response()->json(['error' => 'Please select a vehicle owner for individual ownership.'], 422);
            }
        } elseif (isset($validated['ownership_type']) && $validated['ownership_type'] === 'organization') {
            // Clear owner for organization type
            $validated['owner_id'] = null;
        }
    }

    $validated['updated_by'] = $user->id;

    $vehicle->update($validated);

return response()->json([
    'message' => 'Vehicle updated successfully.',
    'vehicle' => $vehicle->load(['driver', 'creator', 'editor', 'owner']),
]);

}


public function availableForDrivers(Request $request)
{
    // Get IDs of already assigned vehicles
    $assignedVehicleIds = \App\Models\Driver::pluck('vehicle_id')->toArray();

    if ($request->filled('driver_id')) {
        // Get the currently assigned vehicle_id for this driver
        $currentVehicleId = \App\Models\Driver::where('id', $request->driver_id)->value('vehicle_id');

        // Remove the current vehicle from the exclusion list
        $assignedVehicleIds = array_diff($assignedVehicleIds, [$currentVehicleId]);
    }

    // Get unassigned vehicles (plus the current one if editing)
    $vehicles = \App\Models\Vehicle::whereNotIn('id', $assignedVehicleIds)
        ->select('id', 'plate_number', 'model', 'manufacturer')
        ->get();

    return response()->json($vehicles);
}


// Searched by plate number
// Searched by plate number
public function searchByPlate(Request $request)
{
    $this->authorizeAccess('view');
    
    $query = $request->get('q');

    if (!$query) {
        return response()->json([]);
    }

    // Only return vehicles with an assigned driver
    $vehicles = Vehicle::where('plate_number', 'like', '%' . $query . '%')
        ->whereHas('driver') // ✅ ensures vehicle has a driver assigned
        ->with(['driver.user']) // optional: loads driver and user info
        ->limit(10)
        ->get();

    return response()->json($vehicles);
}

    

    // ✅ Delete a vehicle
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }

    

    /**
     * 🔐 Role-based permission checker
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $rolePermissions = [
            'view'   => ['admin', 'manager', 'vehicle_owner', 'gate_security', 'driver'],
            'create' => ['admin', 'manager', 'vehicle_owner'],
            'update' => ['admin', 'manager', 'vehicle_owner'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $rolePermissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
             \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
            abort(403, 'Unauthorized for this action.');
        }
    }
}
