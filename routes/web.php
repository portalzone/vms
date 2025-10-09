<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/{any}', function () {
    $indexPath = public_path('index.html');  // Adjust this path properly
    if (File::exists($indexPath)) {
        return Response::file($indexPath);
    }
    abort(404);
})->where('any', '^(?!api).*$');
