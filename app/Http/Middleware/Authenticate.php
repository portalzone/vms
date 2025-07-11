<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        // ⛔ Prevent redirect to login route for API requests
        if (!$request->expectsJson()) {
            abort(401, 'Unauthenticated.');
        }

        return null;
    }
}
