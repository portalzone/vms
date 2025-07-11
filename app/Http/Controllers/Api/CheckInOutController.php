<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckInOut;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CheckInOutController extends Controller
{
    // ✅ List all check-in/out records with vehicle and driver
    public function index()
    {
        $this->authorizeAccess('view');

        return CheckInOut::with(['vehicle', 'driver'])->latest()->get();
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

        // Prevent duplicate check-in if vehicle hasn't been checked out
        $existing = CheckInOut::where('vehicle_id', $validated['vehicle_id'])
            ->whereNull('checked_out_at')
            ->latest()
            ->first();

        if ($existing && empty($validated['checked_out_at'])) {
            return response()->json([
                'message' => 'This vehicle is already checked in and not yet checked out.'
            ], 422);
        }

        // Auto-fill timestamps if not provided
        $validated['checked_in_at']  = $validated['checked_in_at'] ?? Carbon::now();
        $validated['checked_out_at'] = $validated['checked_out_at'] ?? Carbon::now();

        $record = CheckInOut::create($validated);

        return response()->json([
            'message' => 'Check-in/out record created successfully.',
            'data'    => $record->load(['vehicle', 'driver']),
        ]);
    }

    // ✅ Get a single record by ID with related data
    public function show($id)
    {
        $this->authorizeAccess('view');

        $record = CheckInOut::with(['vehicle', 'driver'])->findOrFail($id);

        return response()->json($record);
    }

    // ✅ Update an existing check-in/out record
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

        // Auto-fill timestamps if needed during update
        if (!isset($validated['checked_in_at']) && !$record->checked_in_at) {
            $validated['checked_in_at'] = Carbon::now();
        }
        if (!isset($validated['checked_out_at']) && !$record->checked_out_at) {
            $validated['checked_out_at'] = Carbon::now();
        }

        $record->update($validated);

        return response()->json([
            'message' => 'Check-in/out record updated successfully.',
            'data'    => $record->load(['vehicle', 'driver']),
        ]);
    }

    // ✅ Delete a record
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
