<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
    'portal_url' => $_ENV['AUTH_PORTAL_URL'] ?? 'http://localhost:4173',
    
    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
        'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With'),
    ],
    
    'roles' => [
        'default' => $_ENV['DEFAULT_USER_ROLE'] ?? 'subscriber',
        'admin_emails' => explode(',', $_ENV['ADMIN_EMAILS'] ?? ''),
    ],
];
