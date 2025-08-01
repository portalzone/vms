<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    // List incomes
    public function index(Request $request)
    {
        $this->authorizeAccess('view');

        $query = Income::with(['vehicle', 'driver', 'trip'])->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('source', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        return response()->json($query->paginate(10));
    }

    // Store new income
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'nullable|exists:drivers,id',
            'trip_id'    => 'nullable|exists:trips,id',
            'source'     => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'description'=> 'nullable|string',
            'date'       => 'required|date',
        ]);

        $income = Income::create($validated);

        return response()->json($income->load(['vehicle', 'driver', 'trip']), 201);
    }

    // Show income
    public function show(Income $income)
    {
        $this->authorizeAccess('view');
        return response()->json($income->load(['vehicle', 'driver', 'trip']));
    }

    // Update income
    public function update(Request $request, Income $income)
    {
        $this->authorizeAccess('update');

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id'  => 'nullable|exists:drivers,id',
            'trip_id'    => 'nullable|exists:trips,id',
            'source'     => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'description'=> 'nullable|string',
            'date'       => 'required|date',
        ]);

        $income->update($validated);

        return response()->json($income->load(['vehicle', 'driver', 'trip']));
    }

    // Delete income
    public function destroy(Income $income)
    {
        $this->authorizeAccess('delete');
        $income->delete();
        return response()->json(['message' => 'Income deleted']);
    }

    // 🔒 Admin-only access
    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        $map = [
            'view'   => ['admin', 'manager'],
            'create' => ['admin', 'manager'],
            'update' => ['admin', 'manager'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $map[$action] ?? [];

        if (! $user || ! $user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
