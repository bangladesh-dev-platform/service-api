<?php

declare(strict_types=1);

namespace App\Domain\Video;

interface VideoHistoryRepository
{
    public function record(string $userId, string $videoId, ?int $positionSeconds = null, array $context = []): VideoHistoryEntry;

    /**
     * @return array<int, array{history: VideoHistoryEntry, video: Video}>
     */
    public function listWithVideos(string $userId, int $limit, int $offset): array;
}
