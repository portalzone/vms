<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    // ✅ Get all expenses
    public function index(Request $request)
    {
        $this->authorizeAccess('view');

        return Expense::with('vehicle')->latest()->get();
    }

    // ✅ Store a new expense
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'vehicle_id'  => 'required|exists:vehicles,id',
            'amount'      => 'required|numeric',
            'description' => 'required|string',
            'date'        => 'required|date',
        ]);

        $expense = Expense::create($validated);

        return response()->json($expense, 201);
    }

    // ✅ Show a single expense
    public function show($id)
    {
        $this->authorizeAccess('view');

        return Expense::with('vehicle')->findOrFail($id);
    }

    // ✅ Update an existing expense
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id'  => 'sometimes|exists:vehicles,id',
            'amount'      => 'sometimes|numeric',
            'description' => 'sometimes|string',
            'date'        => 'sometimes|date',
        ]);

        $expense->update($validated);

        return response()->json($expense);
    }

    // ✅ Delete an expense
    public function destroy($id)
    {
        $this->authorizeAccess('delete');

        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted']);
    }

    /**
     * 🔐 Centralized role-based access control.
     */
    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        $permissions = [
            'view'   => ['admin', 'manager'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $permissions[$action] ?? [];

        if (!$user || !$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
