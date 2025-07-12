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
    return Driver::with(['user', 'vehicle'])->get(); // 👈 make sure 'user' is included
}



    // ✅ Store a new driver with validation
public function store(Request $request)
{
    $this->authorizeAccess('create');

    $validated = $request->validate([
        'user_id'              => 'required|exists:users,id|unique:drivers,user_id',
        'license_number'       => 'required|string|max:50|unique:drivers',
        'phone_number'         => 'required|string|max:20',
        'home_address'         => 'required|string|max:255',
        'sex'                  => 'required|in:male,female,other',
        'vehicle_id'           => 'nullable|exists:vehicles,id',
    ]);

    // Assign 'driver' role to user if not already
    $user = \App\Models\User::findOrFail($validated['user_id']);
    if (!$user->hasRole('driver')) {
        // $user->assignRole('driver', 'api'); // 👈 specify the 'api' guard
        $user->assignRole('driver');


    }

    
    $driver = Driver::create($validated);

    return response()->json($driver->load(['user', 'vehicle']), 201);
}

// 
public function me()
{
    $user = auth()->user();

    if (!$user->hasRole('driver')) {
        abort(403, 'Not a driver.');
    }

    $driver = \App\Models\Driver::with('vehicle')
        ->where('user_id', $user->id)
        ->firstOrFail();

    return response()->json($driver);
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

    $driver = Driver::with('user')->findOrFail($id);

    $validated = $request->validate([
        'user_id'         => 'sometimes|required|exists:users,id|unique:drivers,user_id,' . $id,
        'license_number'  => 'sometimes|required|string|max:50|unique:drivers,license_number,' . $id,
        'phone_number'    => 'sometimes|required|string|max:20',
        'home_address'    => 'sometimes|required|string|max:255',
        'sex'             => 'sometimes|required|in:male,female,other',
        'vehicle_id'      => 'nullable|exists:vehicles,id',
        'name'            => 'sometimes|required|string|max:100',
        'email'           => 'sometimes|required|email|max:100|unique:users,email,' . $driver->user_id,
    ]);

    // ✅ Update user_id if changed
    if (isset($validated['user_id']) && $validated['user_id'] != $driver->user_id) {
        $newUser = \App\Models\User::findOrFail($validated['user_id']);

        // Optionally reassign role if needed
        if (!$newUser->hasRole('driver')) {
            $newUser->assignRole('driver');

            
        }

        $driver->user_id = $validated['user_id'];
    }

    // ✅ Update other driver fields
    $driver->license_number = $validated['license_number'] ?? $driver->license_number;
    $driver->phone_number   = $validated['phone_number'] ?? $driver->phone_number;
    $driver->home_address   = $validated['home_address'] ?? $driver->home_address;
    $driver->sex            = $validated['sex'] ?? $driver->sex;
    $driver->vehicle_id     = $validated['vehicle_id'] ?? $driver->vehicle_id;
    $driver->save();

    // ✅ Update linked user info if provided
    if (isset($validated['name']) || isset($validated['email'])) {
        $driver->user->update([
            'name'  => $validated['name'] ?? $driver->user->name,
            'email' => $validated['email'] ?? $driver->user->email,
        ]);
    }

    return response()->json($driver->load(['user', 'vehicle']));
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
