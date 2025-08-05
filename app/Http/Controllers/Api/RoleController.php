<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
   // app/Http/Controllers/Api/RoleController.php


public function index(Request $request)
{
    $user = auth()->user();

    if ($user->hasRole('gate_security')) {
        $roles = Role::whereIn('name', ['staff', 'visitor'])->where('guard_name', 'api')->get();
    } elseif ($user->hasRole('manager')) {
        $roles = Role::whereIn('name', ['staff', 'visitor', 'driver', 'vehicle_owner', 'gate_security'])
            ->where('guard_name', 'api')->get();
    } else {
        // Admin or other unrestricted roles
        $roles = Role::where('guard_name', 'api')->get();
    }

    return response()->json($roles);
}

}
