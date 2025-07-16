<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInOut;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CheckInOutController extends Controller
{
    // ✅ List check-ins with pagination, search, vehicle + driver (+ user)
    public function index(Request $request)
    {
        $this->authorizeAccess('view');

        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        $query = CheckInOut::with(['vehicle', 'driver.user'])->latest();

        if ($search) {
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->where('plate_number', 'like', '%' . $search . '%');
            })->orWhereHas('driver.user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        return response()->json($query->paginate($perPage));
    }

    // ✅ Create a new check-in record
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $driver = Driver::where('vehicle_id', $validated['vehicle_id'])->first();

        if (!$driver) {
            return response()->json(['message' => 'No driver is assigned to this vehicle.'], 422);
        }

        // Prevent duplicate check-in
        $alreadyCheckedIn = CheckInOut::where('vehicle_id', $validated['vehicle_id'])
            ->whereNull('checked_out_at')
            ->first();

        if ($alreadyCheckedIn) {
            return response()->json(['message' => 'This vehicle is already checked in.'], 422);
        }

        $validated['driver_id'] = $driver->id;
        $validated['checked_in_at'] = now();

        $checkIn = CheckInOut::create($validated);

        return response()->json([
            'message' => 'Check-in successful.',
            'data' => $checkIn->load(['vehicle', 'driver.user']),
        ]);
    }

    // ✅ Custom check-out method
    public function checkout($id)
    {
        $this->authorizeAccess('update');

        $checkIn = CheckInOut::whereNull('checked_out_at')->findOrFail($id);

        $checkIn->checked_out_at = now();
        $checkIn->save();

        return response()->json([
            'message' => 'Check-out successful.',
            'data' => $checkIn->load(['vehicle', 'driver.user']),
        ]);
    }

    // ✅ Show a single record
    public function show($id)
    {
        $this->authorizeAccess('view');

        return response()->json(
            CheckInOut::with(['vehicle', 'driver.user'])->findOrFail($id)
        );
    }

    // ✅ Update a record
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $record = CheckInOut::findOrFail($id);

        $validated = $request->validate([
            'checked_out_at' => 'nullable|date',
        ]);

        // If user manually sets checked_out_at, use it, else use now()
        $validated['checked_out_at'] = $validated['checked_out_at'] ?? now();

        $record->update($validated);

        return response()->json([
            'message' => 'Check-in/out record updated.',
            'data' => $record->load(['vehicle', 'driver.user']),
        ]);
    }

    // ✅ Delete
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $record = CheckInOut::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Check-in/out record deleted.']);
    }

    // 🔐 Role-based access
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $roles = [
            'view'   => ['admin', 'manager', 'driver', 'gate_security'],
            'create' => ['admin', 'manager', 'gate_security'],
            'update' => ['admin', 'manager', 'gate_security'],
            'delete' => ['admin'],
        ];

        if (!$user || !$user->hasAnyRole($roles[$action] ?? [])) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
