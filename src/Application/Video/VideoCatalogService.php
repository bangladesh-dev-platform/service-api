<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Domain\Video\Video;
use App\Domain\Video\VideoRepository;

class VideoCatalogService
{
    public function __construct(private VideoRepository $videos)
    {
    }

    public function upsert(array $payload): Video
    {
        $sourceType = strtolower(trim((string)($payload['source_type'] ?? 'youtube')));
        $sourceRef = trim((string)($payload['source_ref'] ?? ''));
        $title = trim((string)($payload['title'] ?? ''));

        if ($sourceRef === '' || $title === '') {
            throw new \InvalidArgumentException('source_ref and title are required');
        }

        $existing = $this->videos->findBySource($sourceType, $sourceRef);

        $video = new Video(
            id: $existing?->getId(),
            sourceType: $sourceType,
            sourceRef: $sourceRef,
            title: $title,
            description: $payload['description'] ?? $existing?->getDescription(),
            channelName: $payload['channel_name'] ?? $existing?->getChannelName(),
            durationSeconds: isset($payload['duration_seconds'])
                ? (int)$payload['duration_seconds']
                : $existing?->getDurationSeconds(),
            thumbnailUrl: $payload['thumbnail_url'] ?? $existing?->getThumbnailUrl(),
            status: $payload['status'] ?? $existing?->getStatus() ?? 'published',
            visibility: $payload['visibility'] ?? $existing?->getVisibility() ?? 'public',
            tags: $this->sanitizeArray($payload['tags'] ?? $existing?->getTags() ?? []),
            metadata: is_array($payload['metadata'] ?? null)
                ? $payload['metadata']
                : ($existing?->getMetadata() ?? []),
        );

        return $this->videos->save($video);
    }

    public function getVideo(string $id): ?Video
    {
        return $this->videos->findById($id);
    }

    /**
     * @return Video[]
     */
    public function getFeed(?string $category, int $page, int $limit): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        return $this->videos->getFeed($category, $limit, $offset);
    }

    /**
     * @return Video[]
     */
    public function search(?string $query, int $page, int $limit): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $term = $query !== null ? trim($query) : null;
        return $this->videos->search($term, $limit, $offset);
    }

    private function sanitizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), fn($item) => $item !== ''));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $this->sanitizeArray($decoded);
            }
        }

        return [];
    }
}
