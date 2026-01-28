<?php

return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
    'port' => (int)($_ENV['MAIL_PORT'] ?? 2525),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? '',
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@banglade.sh',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Bangladesh Auth',
];
