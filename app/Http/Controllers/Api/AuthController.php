<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ✅ Register a new user
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        // Optional: assign default role
        // $user->assignRole('user');

        $token = $user->createToken('api-token')->plainTextToken;
        $role = $user->getRoleNames()->first(); // returns single role as string

        return response()->json([
            'token' => $token,
            'user'  => $user->only(['id', 'name', 'email']) + ['role' => $role],
        ], 201);
    }

    // ✅ Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        $role = $user->getRoleNames()->first(); // string

        return response()->json([
            'token' => $token,
            'user'  => $user->only(['id', 'name', 'email']) + ['role' => $role],
        ]);
    }

    // ✅ Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

// 🔒 Get authenticated user
public function me(Request $request)
{
    $user = $request->user();
    $role = $user->getRoleNames()->first(); // Spatie gives you the actual name string

    return response()->json([
        'user' => $user->only(['id', 'name', 'email']) + ['role' => $role],
    ]);
}


    // ✅ Admin-only access control
    private function authorizeAccess(string $action)
    {
        $user = auth()->user();

        $map = [
            'view'   => ['admin'],
            'create' => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ];

        $allowedRoles = $map[$action] ?? [];

        if (! $user || ! $user->hasAnyRole($allowedRoles)) {
            abort(403, 'Unauthorized for this action.');
        }
    }
}
