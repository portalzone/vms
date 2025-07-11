<?php

return [

    // Allow your frontend origin to access API routes
    'paths' => ['api/*', 'login', 'logout', 'register'],

    // Allow all HTTP methods (GET, POST, PUT, DELETE, etc.)
    'allowed_methods' => ['*'],

    // Vue dev server
    'allowed_origins' => ['http://localhost:5173'],

    // Leave this empty unless you're using regex for origin matching
    'allowed_origins_patterns' => [],

    // Allow all headers
    'allowed_headers' => ['*'],

    // Expose headers if you ever need to read custom response headers
    'exposed_headers' => [],

    // Optional: how long preflight responses can be cached (in seconds)
    'max_age' => 0,

    // ❌ Set this to false for token-based auth (only true for cookie auth)
    'supports_credentials' => false,

];
