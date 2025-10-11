<?php

use Illuminate\Support\Facades\Route;

// API routes are in routes/api.php

// SPA fallback - serves Vue app for all non-API routes
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api).*$');
