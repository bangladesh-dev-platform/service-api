<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Domain\Video\VideoBookmarkRepository;
use App\Domain\Video\VideoHistoryRepository;
use App\Domain\Video\VideoRepository;

class VideoEngagementService
{
    public function __construct(
        private VideoRepository $videos,
        private VideoBookmarkRepository $bookmarks,
        private VideoHistoryRepository $history,
    ) {
    }

    public function createBookmark(string $userId, string $videoId, ?string $notes = null): array
    {
        $video = $this->videos->findById($videoId);
        if (!$video) {
            throw new \InvalidArgumentException('Video not found');
        }

        $bookmark = $this->bookmarks->create($userId, $videoId, $notes);
        return [
            'bookmark' => $bookmark,
            'video' => $video,
        ];
    }

    public function listBookmarks(string $userId, int $page, int $limit): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        return $this->bookmarks->listWithVideos($userId, $limit, $offset);
    }

    public function deleteBookmark(string $userId, string $videoId): void
    {
        $this->bookmarks->delete($userId, $videoId);
    }

    public function recordHistory(string $userId, string $videoId, ?int $positionSeconds = null, array $context = []): array
    {
        $video = $this->videos->findById($videoId);
        if (!$video) {
            throw new \InvalidArgumentException('Video not found');
        }

        $entry = $this->history->record($userId, $videoId, $positionSeconds, $context);

        return [
            'history' => $entry,
            'video' => $video,
        ];
    }

    public function listHistory(string $userId, int $page, int $limit): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        return $this->history->listWithVideos($userId, $limit, $offset);
    }
}
