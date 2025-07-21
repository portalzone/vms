<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    // ✅ Get all drivers with user and vehicle relationships
    public function index()
    {
        $this->authorizeAccess('view');
        return Driver::with(['user', 'vehicle', 'creator', 'editor'])->get();
    }

    // ✅ Store a new driver
public function store(Request $request)
{
    $this->authorizeAccess('create');

    $validated = $request->validate([
        'user_id'        => 'required|exists:users,id|unique:drivers,user_id',
        'license_number' => 'required|string|max:50|unique:drivers',
        'phone_number'   => 'required|string|max:20',
        'home_address'   => 'required|string|max:255',
        'sex'            => 'required|in:male,female,other',
        'vehicle_id'     => 'nullable|exists:vehicles,id|unique:drivers,vehicle_id',
    ]);

    $user = User::findOrFail($validated['user_id']);
    if (!$user->hasRole('driver')) {
        $user->assignRole('driver');
    }

    $driver = Driver::create([
        ...$validated,
        'created_by' => auth()->id(),
    ]);

    return response()->json($driver->load(['user', 'vehicle']), 201);
}


    // ✅ Authenticated driver's data
    public function me()
    {
        $user = auth()->user();

        $driver = Driver::with('vehicle')->where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json(['message' => 'Driver profile not found.'], 404);
        }

        return response()->json($driver);
    }

    // ✅ Get driver by vehicle ID (for TripForm.vue)
    public function getDriverUserIdByVehicle($vehicleId)
    {
        $driver = Driver::with('user')->where('vehicle_id', $vehicleId)->first();

        if (!$driver) {
            return response()->json(['message' => 'No driver assigned to this vehicle.'], 404);
        }

        return response()->json([
            'user_id' => $driver->user_id,
            'name'    => $driver->user->name,
            'license' => $driver->license_number,
        ]);
    }

    // ✅ Show a single driver
    public function show($id)
    {
        $this->authorizeAccess('view');
        return Driver::with(['user', 'vehicle', 'creator', 'editor'])->findOrFail($id);
    }

    // ✅ Update a driver
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $driver = Driver::with('user')->findOrFail($id);

     $validated = $request->validate([
    'user_id'        => 'sometimes|required|exists:users,id|unique:drivers,user_id,' . $id,
    'license_number' => 'sometimes|required|string|max:50|unique:drivers,license_number,' . $id,
    'phone_number'   => 'sometimes|required|string|max:20',
    'home_address'   => 'sometimes|required|string|max:255',
    'sex'            => 'sometimes|required|in:male,female,other',
    'vehicle_id'     => 'nullable|exists:vehicles,id|unique:drivers,vehicle_id,' . $id,
    'name'           => 'sometimes|required|string|max:100',
    'email'          => 'sometimes|required|email|max:100|unique:users,email,' . $driver->user_id,
]);


        // Handle potential user change
        if (isset($validated['user_id']) && $validated['user_id'] != $driver->user_id) {
            $newUser = User::findOrFail($validated['user_id']);
            if (!$newUser->hasRole('driver')) {
                $newUser->assignRole('driver');
            }
            $driver->user_id = $validated['user_id'];
        }

        // Update driver model fields
        $driver->license_number = $validated['license_number'] ?? $driver->license_number;
        $driver->phone_number   = $validated['phone_number'] ?? $driver->phone_number;
        $driver->home_address   = $validated['home_address'] ?? $driver->home_address;
        $driver->sex            = $validated['sex'] ?? $driver->sex;
        $driver->vehicle_id     = $validated['vehicle_id'] ?? $driver->vehicle_id;
        $driver->updated_by = auth()->id();
        $driver->save();


        // Update user data if provided
        if (isset($validated['name']) || isset($validated['email'])) {
            $driver->user->update([
                'name'  => $validated['name'] ?? $driver->user->name,
                'email' => $validated['email'] ?? $driver->user->email,
            ]);
        }

        return response()->json($driver->load(['user', 'vehicle']));
    }

    // ✅ Delete a driver
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json(['message' => 'Driver deleted']);
    }

    /**
     * 🔐 Role-based access control
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $roles = [
            'view'   => ['admin', 'manager',  'gate_security'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager'],
            'delete' => ['admin'],
        ];

        if (!$user || !$user->hasAnyRole($roles[$action] ?? [])) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
