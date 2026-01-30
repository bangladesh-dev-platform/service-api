<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DownloadServiceClient
{
    private Client $client;
    private string $serviceToken;

    public function __construct(private array $config)
    {
        $baseUrl = rtrim($config['base_url'] ?? 'http://yt-dlp-service-app:8085', '/');
        $timeout = isset($config['timeout']) ? (float)$config['timeout'] : 15.0;

        $this->client = new Client([
            'base_uri' => $baseUrl . '/',
            'timeout' => $timeout,
            'http_errors' => false,
        ]);

        $this->serviceToken = (string)($config['token'] ?? '');
    }

    /**
     * @throws \RuntimeException
     */
    public function fetchManifest(string $videoId, string $sourceRef, ?array $tiers = null, bool $audioOnly = true): array
    {
        $payload = [
            'videoId' => $videoId,
            'sourceRef' => $sourceRef,
            'audioOnly' => $audioOnly,
        ];

        if (!empty($tiers)) {
            $payload['tiers'] = array_values(array_filter($tiers));
        }

        try {
            $response = $this->client->post('extract', [
                'headers' => $this->buildHeaders(),
                'json' => $payload,
            ]);
        } catch (GuzzleException $exception) {
            throw new \RuntimeException('Download service is unreachable', 0, $exception);
        }

        $status = $response->getStatusCode();
        $body = (string)$response->getBody();
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            $message = is_array($decoded) ? ($decoded['message'] ?? 'Download service error') : 'Download service error';
            throw new \RuntimeException($message);
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid response from download service');
        }

        return $decoded;
    }

    private function buildHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->serviceToken !== '') {
            $headers['X-Service-Token'] = $this->serviceToken;
        }

        return $headers;
    }
}
