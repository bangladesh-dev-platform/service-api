<?php

declare(strict_types=1);

namespace App\Domain\Video;

class VideoBookmark
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $videoId,
        private ?string $notes,
        private string $createdAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'video_id' => $this->videoId,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            userId: $row['user_id'],
            videoId: $row['video_id'],
            notes: $row['notes'] ?? null,
            createdAt: $row['created_at'] ?? (new \DateTimeImmutable())->format(DATE_ATOM)
        );
    }
}
