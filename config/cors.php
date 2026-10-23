<?php
 
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => ['#^http://(localhost|127\.0\.0\.1)(:\d+)?$#'],
    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
