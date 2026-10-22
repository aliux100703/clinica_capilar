<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type'],
    'exposed_headers' => ['Retry-After'],
    'max_age' => 600,
    'supports_credentials' => false,
];
