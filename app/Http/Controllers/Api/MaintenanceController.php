<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    // ✅ List all maintenance records
public function index()
{
    $this->authorizeAccess('view');
    $user = auth()->user();

    if ($user->hasRole('admin') || $user->hasRole('manager')) {
        return Maintenance::with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->latest()->get();
    }

    if ($user->hasRole('vehicle_owner')) {
        return Maintenance::whereHas('vehicle', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->latest()->get();
    }

    if ($user->hasRole('driver')) {
        return Maintenance::whereHas('vehicle.drivers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->latest()->get();
    }

    return response()->json(['message' => 'Forbidden'], 403);
}

    // ✅ Store new maintenance record and auto-create expense
// Store new maintenance record
public function store(Request $request)
{
            $this->authorizeAccess('create');


    $validated = $request->validate([
        'vehicle_id'  => 'required|exists:vehicles,id',
        'description' => 'required|string',
        'status'      => 'required|string|in:Pending,in_progress,Completed',
        'cost'        => 'nullable|numeric',
        'date'        => 'required|date',
    ]);

    $userId = auth()->id();
        $user = auth()->user();
        // $userId = auth()->user();

if (in_array($validated['status'], ['Completed']) && $user->hasRole(['driver', 'vehicle_owner'])) {
    return response()->json(['error' => 'You are not authorized to mark maintenance as completed.'], 403);
}


    // Prepare maintenance data
    $maintenanceData = [
        'vehicle_id'  => $validated['vehicle_id'],
        'description' => $validated['description'],
        'status'      => $validated['status'],
        'date'        => $validated['date'],
        'created_by'  => $userId,
        'updated_by'  => $userId,
        'cost'        => $validated['cost'] ?? 0,
    ];

    // Only set cost if status is Completed
    if ($validated['status'] === 'Completed') {
        $maintenanceData['cost'] = $validated['cost'] ?? 0;
    }

    $maintenance = Maintenance::create($maintenanceData);

    // Create an expense ONLY if maintenance is completed
    if ($maintenance->status === 'Completed') {
        Expense::create([
            'vehicle_id'     => $maintenance->vehicle_id,
            'maintenance_id' => $maintenance->id,
            'amount'         => $maintenance->cost ?? 0,
            'description'    => 'Maintenance: ' . $maintenance->description,
            'date'           => $maintenance->date,
            'created_by'     => $userId,
            'updated_by'     => $userId,
        ]);
    }

    return response()->json($maintenance->load(['vehicle', 'expense', 'createdBy', 'updatedBy']), 201);
}



    // ✅ Show one maintenance record
    public function show($id)
    {
        $this->authorizeAccess('view');
        $record = Maintenance::with(['vehicle', 'expense', 'createdBy', 'updatedBy'])->findOrFail($id);
        return response()->json($record);
    }

    // ✅ Update maintenance and its linked expense
    // Update maintenance and trigger expense only if completed
public function update(Request $request, $id)
{
            $this->authorizeAccess('update');

    $maintenance = Maintenance::findOrFail($id);

    $validated = $request->validate([
        'vehicle_id'  => 'sometimes|exists:vehicles,id',
        'description' => 'sometimes|string',
        'status'      => 'sometimes|string|in:Pending,in_progress,Completed',
        'cost'        => 'nullable|numeric',
        'date'        => 'sometimes|date',
    ]);

if (isset($validated['status']) && $validated['status'] === 'Completed' && auth()->user()->hasAnyRole(['driver', 'vehicle_owner'])) {
    return response()->json(['error' => 'You are not authorized to mark maintenance as completed.'], 403);
}


    $maintenance->update([
        ...$validated,
        'updated_by' => auth()->id(),
    ]);

    // Handle expense creation/update only if status is Completed
    if (($validated['status'] ?? $maintenance->status) === 'Completed') {
        $expenseData = [
            'vehicle_id'     => $maintenance->vehicle_id,
            'amount'         => $maintenance->cost ?? 0,
            'description'    => 'Maintenance: ' . $maintenance->description,
            'date'           => $maintenance->date,
            'updated_by'     => auth()->id(),
        ];

        if ($maintenance->expense) {
            $maintenance->expense->update($expenseData);
        } else {
            $expenseData['maintenance_id'] = $maintenance->id;
            $expenseData['created_by'] = auth()->id();
            Expense::create($expenseData);
        }
    }

    return response()->json($maintenance->load(['vehicle', 'expense', 'createdBy', 'updatedBy']));
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
            'view'   => ['admin', 'manager', 'vehicle_owner', 'driver'],
            'create' => ['admin', 'manager', 'vehicle_owner', 'driver'],
            'update' => ['admin', 'manager', 'driver'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $map[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
             \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
            abort(403, 'Unauthorized for this action.');
        }
    }
}
