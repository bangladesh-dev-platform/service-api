<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Domain\Video\VideoRepository;
use App\Infrastructure\Http\DownloadServiceClient;

class VideoDownloadService
{
    public function __construct(
        private VideoRepository $videos,
        private DownloadServiceClient $client,
        private array $downloadConfig = []
    ) {
    }

    public function getManifest(string $videoId): ?array
    {
        $video = $this->videos->findById($videoId);

        if (!$video) {
            return null;
        }

        if (strtolower($video->getSourceType()) !== 'youtube') {
            return $this->buildUnavailableResponse($videoId, $video->getSourceType());
        }

        $tiers = $this->downloadConfig['tiers'] ?? null;

        $response = $this->client->fetchManifest(
            $video->getId() ?? $videoId,
            $video->getSourceRef(),
            is_array($tiers) ? $tiers : null
        );

        $manifest = $response['manifest'] ?? null;

        if (!is_array($manifest)) {
            throw new \RuntimeException('Download service returned an invalid payload');
        }

        return $this->transformManifest($manifest, $videoId);
    }

    private function transformManifest(array $manifest, string $fallbackId): array
    {
        $formats = array_map(function (array $format) {
            return [
                'id' => $format['id'] ?? null,
                'label' => $format['label'] ?? null,
                'quality' => $format['quality'] ?? null,
                'container' => $format['container'] ?? null,
                'codec' => $format['codec'] ?? null,
                'size_bytes' => isset($format['sizeBytes']) ? (int)$format['sizeBytes'] : null,
                'type' => $format['type'] ?? null,
                'download_url' => $format['downloadUrl'] ?? null,
                'expires_at' => $format['expiresAt'] ?? null,
            ];
        }, $manifest['formats'] ?? []);

        return [
            'video_id' => $manifest['videoId'] ?? $fallbackId,
            'source_type' => $manifest['sourceType'] ?? 'youtube',
            'formats' => $formats,
            'no_formats_reason' => $manifest['noFormatsReason'] ?? null,
        ];
    }

    private function buildUnavailableResponse(string $videoId, string $sourceType): array
    {
        return [
            'video_id' => $videoId,
            'source_type' => $sourceType,
            'formats' => [],
            'no_formats_reason' => 'DOWNLOADS_NOT_SUPPORTED',
        ];
    }
}
