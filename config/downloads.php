<?php

declare(strict_types=1);

return [
    'base_url' => rtrim($_ENV['YTDLP_SERVICE_URL'] ?? 'http://yt-dlp-service-app:8085', '/'),
    'token' => $_ENV['YTDLP_SERVICE_TOKEN'] ?? '',
    'timeout' => (int) ($_ENV['YTDLP_SERVICE_TIMEOUT'] ?? 15),
    'tiers' => array_filter(array_map('trim', explode(',', $_ENV['YTDLP_SERVICE_TIERS'] ?? '1080p,720p,480p,audio'))),
];
