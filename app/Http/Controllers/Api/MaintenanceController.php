<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Expense;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    // ✅ List all maintenance records
    public function index()
    {
        $this->authorizeAccess('view');
        return Maintenance::with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->latest()->get();
    }

    // ✅ Store new maintenance record and auto-create expense
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'  => 'required|exists:vehicles,id',
            'description' => 'required|string',
            'status'      => 'required|string|in:Pending,in_progress,Completed',
            'cost'        => 'required|numeric',
            'date'        => 'required|date',
        ]);

        $userId = auth()->id();

        $maintenance = Maintenance::create([
            ...$validated,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // 🔗 Automatically link to an Expense
        Expense::create([
            'vehicle_id'     => $validated['vehicle_id'],
            'maintenance_id' => $maintenance->id,
            'amount'         => $validated['cost'],
            'description'    => 'Maintenance: ' . $validated['description'],
            'date'           => $validated['date'],
            'created_by'     => $userId,
            'updated_by'     => $userId,
        ]);

        return response()->json($maintenance->load(['vehicle', 'expense']), 201);
    }

    // ✅ Show one maintenance record
    public function show($id)
    {
        $this->authorizeAccess('view');
        $record = Maintenance::with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->findOrFail($id);
        return response()->json($record);
    }

    // ✅ Update maintenance and its linked expense
    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id'  => 'sometimes|exists:vehicles,id',
            'description' => 'sometimes|string',
            'status'      => 'sometimes|string|in:Pending,in_progress,Completed',
            'cost'        => 'sometimes|numeric',
            'date'        => 'sometimes|date',
        ]);

        $maintenance->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        // 🔁 Also update the linked expense
        if ($maintenance->expense) {
            $maintenance->expense->update([
                'vehicle_id'     => $maintenance->vehicle_id,
                'amount'         => $maintenance->cost,
                'description'    => 'Maintenance: ' . $maintenance->description,
                'date'           => $maintenance->date,
                'updated_by'     => auth()->id(),
            ]);
        }

        return response()->json($maintenance->load(['vehicle', 'expense']));
    }

    // ✅ Filter maintenance records by vehicle
    public function byVehicle($id)
    {
        $this->authorizeAccess('view');
        return Maintenance::with('expense')
            ->where('vehicle_id', $id)
            ->orderByDesc('date')
            ->get();
    }

    // ✅ Delete a record and its expense
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $maintenance = Maintenance::findOrFail($id);
        Expense::where('maintenance_id', $maintenance->id)->delete();
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance record and expense deleted.']);
    }

    // 🔐 Role-based permission logic
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
