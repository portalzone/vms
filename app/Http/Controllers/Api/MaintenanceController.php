<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    // ✅ List all maintenance records
    public function index()
    {
        $this->authorizeAccess('view');

        return Maintenance::with('vehicle')->latest()->get();
    }

    // ✅ Store new maintenance record
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'vehicle_id'  => 'required|exists:vehicles,id',
            'description' => 'required|string',
            'status'      => 'required|in:Pending,In Progress,Completed',
        ]);

        $record = Maintenance::create($validated);

        return response()->json([
            'message' => 'Maintenance record created.',
            'data'    => $record->load('vehicle'),
        ]);
    }

    // ✅ Show one maintenance record
    public function show($id)
    {
        $this->authorizeAccess('view');

        $record = Maintenance::with('vehicle')->findOrFail($id);

        return response()->json($record);
    }

    // ✅ Update maintenance record
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $record = Maintenance::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id'  => 'sometimes|exists:vehicles,id',
            'description' => 'sometimes|string',
            'status'      => 'sometimes|in:Pending,In Progress,Completed',
        ]);

        $record->update($validated);

        return response()->json([
            'message' => 'Maintenance record updated.',
            'data'    => $record->load('vehicle'),
        ]);
    }

    // ✅ Delete a record
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $record = Maintenance::findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Maintenance record deleted.']);
    }

    /**
     * 🔐 Role-based permission logic.
     */
    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        $map = [
            'view'   => ['admin', 'manager', 'vehicle_owner'],
            'create' => ['admin', 'manager', 'vehicle_owner'],
            'update' => ['admin', 'manager', 'vehicle_owner'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $map[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
