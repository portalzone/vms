<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;


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

public function profileHistory()
{
    $user = Auth::user();

    // Get paginated activities
    $logs = Activity::where('log_name', 'user')
        ->where('causer_id', $user->id)
        ->orderByDesc('created_at')
        ->paginate(1); // paginate instead of take(20)

    // Map each activity item to a structured array
    $transformed = $logs->getCollection()->map(function ($log) {
        return [
            'description' => $log->description,
            'changes' => $log->properties['attributes'] ?? [],
            'old' => $log->properties['old'] ?? [],
            'date' => $log->created_at->toDateTimeString(),
        ];
    });

    // Replace the original collection with the transformed one
    $logs->setCollection($transformed);

    return response()->json($logs);
}



    // ✅ Delete a user
    public function destroy($id)
    {
        $this->authorizeAccess('delete');
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }


public function usersAvailableForDriverForm(Request $request)
{
    $driverId = $request->query('driver_id'); // e.g. ?driver_id=5
    $assignedUserIds = Driver::pluck('user_id')->toArray();

    if ($driverId) {
        $currentDriver = Driver::find($driverId);
        if ($currentDriver) {
            // Remove currently assigned user from the exclusion list
            $assignedUserIds = array_diff($assignedUserIds, [$currentDriver->user_id]);
        }
    }

    $users = User::whereNotIn('id', $assignedUserIds)
        ->select('id', 'name', 'email')
        ->get();

    return response()->json($users);
}
// user available for driver
public function availableForDrivers(Request $request)
{
    $assignedUserIds = \App\Models\Driver::pluck('user_id')->toArray();

    if ($request->filled('driver_id')) {
        // Get the currently assigned user_id for this driver
        $currentUserId = \App\Models\Driver::where('id', $request->driver_id)->value('user_id');

        // Allow this user_id in the results
        $assignedUserIds = array_diff($assignedUserIds, [$currentUserId]);
    }

    $users = \App\Models\User::role('driver')
        ->whereNotIn('id', $assignedUserIds)
        ->select('id', 'name', 'email')
        ->get();

    return response()->json($users);
}


//user with driver status

public function usersWithDriverStatus()
{
    $assignedUserIds = Driver::pluck('user_id')->toArray();

    $users = User::select('id', 'name', 'email')
        ->get()
        ->map(function ($user) use ($assignedUserIds) {
            $user->already_assigned = in_array($user->id, $assignedUserIds);
            return $user;
        });

    return response()->json($users);
}

// Return authenticated user's profile
public function me(Request $request)
{
    return response()->json($request->user()->load('roles'));
}

 /**
     * Return the profile of the currently authenticated user.
     */
/**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ]);
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
