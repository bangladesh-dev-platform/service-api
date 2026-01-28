<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Domain\Video\Comment;
use App\Domain\Video\CommentRepository;
use App\Domain\Video\VideoRepository;

class CommentService
{
    public function __construct(
        private CommentRepository $comments,
        private VideoRepository $videos,
    ) {
    }

    /**
     * @return Comment[]
     */
    public function list(string $videoId, ?string $parentId, int $page, int $limit): array
    {
        $video = $this->videos->findById($videoId);
        if (!$video) {
            throw new \InvalidArgumentException('Video not found');
        }

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        return $this->comments->listComments($videoId, $parentId, $limit, $offset);
    }

    public function create(string $videoId, string $userId, ?string $parentId, string $body): Comment
    {
        $video = $this->videos->findById($videoId);
        if (!$video) {
            throw new \InvalidArgumentException('Video not found');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Comment body is required');
        }

        if ($parentId) {
            $parent = $this->comments->findById($parentId);
            if (!$parent || $parent->toArray()['video_id'] !== $videoId) {
                throw new \InvalidArgumentException('Invalid parent comment');
            }
        }

        return $this->comments->create($videoId, $userId, $parentId, $body);
    }

    public function delete(string $commentId, string $userId, bool $isAdmin = false): bool
    {
        return $this->comments->softDelete($commentId, $userId, $isAdmin);
    }
}
