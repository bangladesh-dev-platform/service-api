<?php

declare(strict_types=1);

namespace App\Domain\Video;

interface CommentRepository
{
    /**
     * @return Comment[]
     */
    public function listComments(string $videoId, ?string $parentId, int $limit, int $offset): array;

    public function countComments(string $videoId, ?string $parentId): int;

    public function create(string $videoId, string $userId, ?string $parentId, string $body): Comment;

    public function softDelete(string $commentId, string $userId, bool $isAdmin = false): bool;

    public function findById(string $id): ?Comment;
}
