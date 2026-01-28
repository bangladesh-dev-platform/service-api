<?php

return [
    'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this',
    'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
    'issuer' => $_ENV['APP_URL'] ?? 'api.banglade.sh',
    'access_token_expiry' => (int)($_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900), // 15 minutes
    'refresh_token_expiry' => (int)($_ENV['JWT_REFRESH_TOKEN_EXPIRY'] ?? 604800), // 7 days
    'email_verification_expiry' => (int)($_ENV['JWT_EMAIL_VERIFICATION_EXPIRY'] ?? 86400), // 24 hours
];
