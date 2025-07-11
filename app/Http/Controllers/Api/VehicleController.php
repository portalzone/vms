<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    // ✅ List all vehicles
    public function index()
    {
        $this->authorizeAccess('view');
        return Vehicle::all();
    }

    // ✅ Create a new vehicle
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'manufacturer' => 'required|string|max:255',
            'model'        => 'required|string|max:255',
            'year'         => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|max:20|unique:vehicles',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json($vehicle, 201);
    }

    // ✅ Show a specific vehicle
    public function show($id)
    {
        $this->authorizeAccess('view');
        return Vehicle::findOrFail($id);
    }

    // ✅ Update a vehicle
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'manufacturer' => 'sometimes|required|string|max:255',
            'model'        => 'sometimes|required|string|max:255',
            'year'         => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'sometimes|required|string|max:20|unique:vehicles,plate_number,' . $id,
        ]);

        $vehicle->update($validated);

        return response()->json($vehicle);
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
     * 🔐 Role-based permission checker.
     * Define who can perform what action.
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $rolePermissions = [
            'view'   => ['admin', 'manager', 'vehicle_owner'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager', 'vehicle_owner'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $rolePermissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
