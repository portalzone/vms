<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Models\Trip;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DriverController extends Controller
{
    // ✅ Get all drivers with user and vehicle relationships
public function index(Request $request)
{
    $this->authorizeAccess('view');

    $query = Driver::with(['user', 'vehicle', 'creator', 'editor']);

    // Apply filters from query params
    if ($request->has('ownership_type')) {
        $query->whereHas('vehicle', function ($q) use ($request) {
            $q->where('ownership_type', $request->ownership_type);
        });
    }

    if ($request->has('owner_id')) {
        $query->whereHas('vehicle', function ($q) use ($request) {
            $q->where('owner_id', $request->owner_id);
        });
    }

    // Filter by created_by for gate security role
    if (auth()->user()->hasRole('gate_security')) {
        $query->where('created_by', auth()->id());
    }

    // If user is vehicle_owner, show only their drivers
    if (auth()->user()->hasRole('vehicle_owner')) {
        $query->whereHas('vehicle', function ($q) {
            $q->where('owner_id', auth()->id());
        });
    }

    $drivers = $query->latest()->paginate(10);

    return response()->json($drivers);
}


    // ✅ Store a new driver
public function store(Request $request)
{
    $this->authorizeAccess('create');

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id|unique:drivers,user_id',
        'vehicle_id' => 'nullable|exists:vehicles,id',
        'license_number' => 'required|string|max:255',
        'phone_number' => 'required|string|max:20',
        'sex' => 'required|in:male,female',
        'home_address' => 'nullable|string|max:255',
        'driver_type' => 'required|in:staff,visitor,organization,vehicle_owner',
    ]);

    $user = auth()->user();

    // 🛡️ Gate Security can only create 'visitor' drivers
    if ($user->hasRole('gate_security') && $validated['driver_type'] !== 'visitor') {
        return response()->json(['message' => 'Gate Security can only create visitor drivers.'], 403);
    }

    // 🚫 Other roles not allowed (except admin, manager, gate_security)
    if (!$user->hasAnyRole(['admin', 'manager', 'gate_security'])) {
        return response()->json(['message' => 'Unauthorized to create drivers.'], 403);
    }

    // ✅ Passed all checks — create driver
        $validated['created_by'] = $user->id;
    $validated['updated_by'] = $user->id;

    $driver = Driver::create($validated);

    return response()->json($driver, 201);
}


// excel export:
public function exportDriverTripsExcel($id)
{
    $driver = Driver::with(['user', 'vehicle', 'trips'])->findOrFail($id);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Trip ID');
    $sheet->setCellValue('B1', 'Start Location');
    $sheet->setCellValue('C1', 'End Location');
    $sheet->setCellValue('D1', 'Start Time');
    $sheet->setCellValue('E1', 'End Time');

    $row = 2;
    foreach ($driver->trips as $trip) {
        $sheet->setCellValue("A{$row}", $trip->id);
        $sheet->setCellValue("B{$row}", $trip->start_location);
        $sheet->setCellValue("C{$row}", $trip->end_location);
        $sheet->setCellValue("D{$row}", $trip->start_time);
        $sheet->setCellValue("E{$row}", $trip->end_time);
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    $fileName = 'driver_trips_' . $driver->id . '.xlsx';
    $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    $writer->save($temp_file);

    return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
}

    // ✅ Authenticated driver's data
 public function me()
{
    $user = auth()->user();
    $driver = Driver::with('vehicle')->where('user_id', $user->id)->first();

    if (!$driver) {
        return response()->json(null); // or: response()->json(['message' => 'No driver profile'], 200);
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

    $driver = Driver::with([
        'user',
        'vehicle.maintenanceRecords',
        'trips',
        'creator',
        'editor'
    ])->findOrFail($id);

return response()->json([
    'id' => $driver->id,
    'user_id' => $driver->user_id,
    'name' => $driver->user->name ?? 'Unknown',
    'email' => $driver->user->email ?? 'N/A',
    'license_number' => $driver->license_number,
    'phone_number' => $driver->phone_number,
    'sex' => $driver->sex,
    'home_address' => $driver->home_address, // ✅ use correct field name

    'vehicle' => $driver->vehicle ? [
        'id' => $driver->vehicle->id,
        'name' => "{$driver->vehicle->manufacturer} {$driver->vehicle->model}",
        'plate_number' => $driver->vehicle->plate_number,
    ] : null,

    'trips' => $driver->trips->map(function ($trip) {
        return [
            'id' => $trip->id,
            'driver_id' => $trip->driver_id,
            'start_location' => $trip->start_location,
            'end_location' => $trip->end_location,
            'start_time' => $trip->start_time,
            'end_time' => $trip->end_time,
            'created_at' => $trip->created_at->toDateTimeString(),
        ];
    }),

    'maintenance_logs' => $driver->vehicle?->maintenanceRecords->map(function ($m) {
        return [
            'id' => $m->id,
            'description' => $m->description,
            'cost' => $m->cost,
            'date' => $m->date,
        ];
    }) ?? [],

    'created_by' => $driver->creator?->name,
    'updated_by' => $driver->editor?->name,

    'created_at' => $driver->created_at->toDateTimeString(),
    'updated_at' => $driver->updated_at->toDateTimeString(),
]);

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
    'driver_type'    => 'sometimes|required|in:staff,visitor,organization,vehicle_owner',
    'name'           => 'sometimes|required|string|max:100',
    'email'          => 'sometimes|required|email|max:100|unique:users,email,' . $driver->user_id,
]);

$user = auth()->user();

// 🛡️ Restrict gate_security to only assign 'visitor'
if ($user->hasRole('gate_security') && isset($validated['driver_type']) && $validated['driver_type'] !== 'visitor') {
    return response()->json(['message' => 'Gate Security can only assign visitor driver type.'], 403);
}



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
        $driver->driver_type = $validated['driver_type'] ?? $driver->driver_type;
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
            'view'   => ['admin', 'manager',  'gate_security', 'vehicle_owner'],
            'create' => ['admin', 'manager', 'gate_security'],
            'update' => ['admin', 'manager', 'gate_security'],
            'delete' => ['admin'],
        ];

        if (!$user || !$user->hasAnyRole($roles[$action] ?? [])) {
             \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
            abort(403, 'Unauthorized for this action.');
        }
    }
}
