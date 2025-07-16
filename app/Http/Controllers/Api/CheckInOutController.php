<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInOut;
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

        $results = $query->paginate($perPage);

        return response()->json($results);
    }

    // ✅ Store a new check-in/out record
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'driver_id'      => 'required|exists:drivers,id',
            'checked_in_at'  => 'nullable|date',
            'checked_out_at' => 'nullable|date',
        ]);

        // Prevent double check-in if vehicle isn't checked out yet
        $existing = CheckInOut::where('vehicle_id', $validated['vehicle_id'])
            ->whereNull('checked_out_at')
            ->latest()
            ->first();

        if ($existing && empty($validated['checked_out_at'])) {
            return response()->json([
                'message' => 'This vehicle is already checked in and not yet checked out.',
            ], 422);
        }

        // Auto-timestamp if not provided
        $validated['checked_in_at'] = $validated['checked_in_at'] ?? Carbon::now();

        $record = CheckInOut::create($validated);

        return response()->json([
            'message' => 'Check-in/out record created.',
            'data'    => $record->load(['vehicle', 'driver.user']),
        ]);
    }

    // ✅ Show a single check-in/out record
    public function show($id)
    {
        $this->authorizeAccess('view');

        $record = CheckInOut::with(['vehicle', 'driver.user'])->findOrFail($id);

        return response()->json($record);
    }

    // ✅ Update a check-in/out record
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $record = CheckInOut::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id'     => 'sometimes|exists:vehicles,id',
            'driver_id'      => 'sometimes|exists:drivers,id',
            'checked_in_at'  => 'nullable|date',
            'checked_out_at' => 'nullable|date',
        ]);

        if (!isset($validated['checked_in_at']) && !$record->checked_in_at) {
            $validated['checked_in_at'] = Carbon::now();
        }

        if (!isset($validated['checked_out_at']) && !$record->checked_out_at) {
            $validated['checked_out_at'] = Carbon::now();
        }

        $record->update($validated);

        return response()->json([
            'message' => 'Check-in/out record updated.',
            'data'    => $record->load(['vehicle', 'driver.user']),
        ]);
    }

    // ✅ Delete a check-in/out record
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $record = CheckInOut::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Check-in/out record deleted.']);
    }

    /**
     * 🔐 Role-based permission checker.
     */
    private function authorizeAccess(string $action): void
    {
        $user = auth()->user();

        $permissions = [
            'view'   => ['admin', 'manager', 'driver', 'gate_security'],
            'create' => ['admin', 'manager', 'driver', 'gate_security'],
            'update' => ['admin', 'manager', 'driver', 'gate_security'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $permissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
