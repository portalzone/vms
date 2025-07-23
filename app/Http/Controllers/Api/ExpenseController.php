<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
// ExpenseController.php
public function index(Request $request)
{
    $this->authorizeAccess('view');

    $expenses = Expense::with(['vehicle', 'maintenance', 'creator', 'updater'])
        ->latest()
        ->get();

    return response()->json($expenses);
}



public function store(Request $request)
{
    $this->authorizeAccess('create');

    $validated = $request->validate([
        'vehicle_id'     => 'required|exists:vehicles,id',
        'maintenance_id' => 'nullable|exists:maintenances,id',
        'amount'         => 'required|numeric',
        'description'    => 'required|string',
        'date'           => 'required|date',
    ]);

    $validated['created_by'] = auth()->id();

    $expense = Expense::create($validated);

    // ✅ If linked to maintenance, mark it as completed
    if (!empty($validated['maintenance_id'])) {
        \App\Models\Maintenance::where('id', $validated['maintenance_id'])
            ->update(['status' => 'completed']);
    }

    return response()->json(
        $expense->load(['vehicle', 'maintenance', 'creator', 'updater']),
        201
    );
}


    public function show($id)
    {
        $this->authorizeAccess('view');

        return Expense::with(['vehicle', 'maintenance', 'creator', 'updater'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id'     => 'sometimes|exists:vehicles,id',
            'maintenance_id' => 'nullable|exists:maintenances,id',
            'amount'         => 'sometimes|numeric',
            'description'    => 'sometimes|string',
            'date'           => 'sometimes|date',
        ]);

        $validated['updated_by'] = auth()->id();

        $expense->update($validated);

        return response()->json(
            $expense->load(['vehicle', 'maintenance', 'creator', 'updater'])
        );
    }

    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted']);
    }

    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        $permissions = [
            'view'   => ['admin', 'manager'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager'],
            'delete' => ['admin'],
        ];

        if (!$user || !$user->hasAnyRole($permissions[$action] ?? [])) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
