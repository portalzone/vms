<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // ✅ Get all users with roles
    public function index()
    {
        $this->authorizeAccess('view');
        return User::with('roles:id,name')->select('id', 'name', 'email')->get();
    }

    // ✅ Create new user with role assignment (API guard)
    public function store(Request $request)
    {
        $this->authorizeAccess('create');

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::where('name', $validated['role'])->where('guard_name', 'api')->first();
        if (!$role) {
            return response()->json([
                'message' => 'Invalid role for API guard.'
            ], 422);
        }

        $user->assignRole($role);

        return response()->json([
            'message' => 'User created successfully',
            'data'    => $user->load('roles:id,name'),
        ], 201);
    }

    // ✅ Show user with roles
    public function show($id)
    {
        $this->authorizeAccess('view');
        $user = User::with('roles:id,name')->findOrFail($id);
        return response()->json($user);
    }

    // ✅ Update user and role (API guard)
    public function update(Request $request, $id)
    {
        $this->authorizeAccess('update');

        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name'     => 'sometimes|string|max:100',
                'email'    => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'role'     => 'sometimes|string|exists:roles,name',
            ]);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            if (!empty($validated['role'])) {
                $role = Role::where('name', $validated['role'])->where('guard_name', 'api')->first();
                if (!$role) {
                    return response()->json([
                        'message' => 'Invalid role for API guard.'
                    ], 422);
                }
                $user->syncRoles([$role]);
            }

            return response()->json([
                'message' => 'User updated successfully',
                'data'    => $user->load('roles:id,name'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ Delete a user
    public function destroy($id)
    {
        $this->authorizeAccess('delete');
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    // 🔐 Role-based access checker
    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        $map = [
            'view'   => ['admin', 'manager'],
            'create' => ['admin', 'manager'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $map[$action] ?? [];

        if (!$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
