<?php

declare(strict_types=1);

namespace App\Domain\Video;

interface VideoBookmarkRepository
{
    public function create(string $userId, string $videoId, ?string $notes = null): VideoBookmark;

    public function delete(string $userId, string $videoId): void;

    /**
     * @return array<int, array{bookmark: VideoBookmark, video: Video}>
     */
    public function listWithVideos(string $userId, int $limit, int $offset): array;
}
