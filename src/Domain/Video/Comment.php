<?php

declare(strict_types=1);

namespace App\Domain\Video;

class Comment
{
    public function __construct(
        private string $id,
        private string $videoId,
        private string $userId,
        private ?string $parentId,
        private string $body,
        private int $likeCount,
        private bool $isDeleted,
        private string $createdAt,
        private string $updatedAt,
        private ?array $author = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            videoId: $row['video_id'],
            userId: $row['user_id'],
            parentId: $row['parent_id'] ?? null,
            body: $row['body'] ?? '',
            likeCount: (int)($row['like_count'] ?? 0),
            isDeleted: (bool)($row['is_deleted'] ?? false),
            createdAt: $row['created_at'] ?? (new \DateTimeImmutable())->format(DATE_ATOM),
            updatedAt: $row['updated_at'] ?? (new \DateTimeImmutable())->format(DATE_ATOM),
            author: isset($row['author']) && is_array($row['author']) ? $row['author'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'video_id' => $this->videoId,
            'user_id' => $this->userId,
            'parent_id' => $this->parentId,
            'body' => $this->body,
            'like_count' => $this->likeCount,
            'is_deleted' => $this->isDeleted,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'author' => $this->author,
        ];
    }
}
