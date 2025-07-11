<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    // ✅ Get all drivers with vehicle relationship
    public function index()
    {
        $this->authorizeAccess('view');
        return Driver::with('vehicle')->get();
    }

    // ✅ Store a new driver with validation
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'phone_number'          => 'required|string|max:20',
            'home_address'          => 'required|string|max:255',
            'sex'                   => 'required|in:male,female,other',
            'driver_licence_number' => 'required|string|max:50|unique:drivers',
            'vehicle_id'            => 'nullable|exists:vehicles,id',
        ]);

        $driver = Driver::create($validated);

        return response()->json($driver->load('vehicle'), 201);
    }

    // ✅ Show single driver
    public function show($id)
    {
        $this->authorizeAccess('view');
        return Driver::with('vehicle')->findOrFail($id);
    }

    // ✅ Update driver
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $driver = Driver::findOrFail($id);

        $validated = $request->validate([
            'name'                  => 'sometimes|required|string|max:255',
            'phone_number'          => 'sometimes|required|string|max:20',
            'home_address'          => 'sometimes|required|string|max:255',
            'sex'                   => 'sometimes|required|in:male,female,other',
            'driver_licence_number' => 'sometimes|required|string|max:50|unique:drivers,driver_licence_number,' . $id,
            'vehicle_id'            => 'nullable|exists:vehicles,id',
        ]);

        $driver->update($validated);

        return response()->json($driver->load('vehicle'));
    }

    // ✅ Delete driver
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json(['message' => 'Driver deleted']);
    }

    /**
     * 🔐 Role-based permission checker.
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $rolePermissions = [
            'view'   => ['admin', 'manager'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $rolePermissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
