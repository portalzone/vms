<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::select('id', 'name')->get());
    }
}
