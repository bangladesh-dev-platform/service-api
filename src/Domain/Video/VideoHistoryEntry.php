<?php

declare(strict_types=1);

namespace App\Domain\Video;

class VideoHistoryEntry
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $videoId,
        private string $lastWatchedAt,
        private int $lastPositionSeconds,
        private int $watchCount,
        private array $context,
        private string $createdAt,
        private string $updatedAt,
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

    public function getLastWatchedAt(): string
    {
        return $this->lastWatchedAt;
    }

    public function getLastPositionSeconds(): int
    {
        return $this->lastPositionSeconds;
    }

    public function getWatchCount(): int
    {
        return $this->watchCount;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'video_id' => $this->videoId,
            'last_watched_at' => $this->lastWatchedAt,
            'last_position_seconds' => $this->lastPositionSeconds,
            'watch_count' => $this->watchCount,
            'context' => $this->context,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            userId: $row['user_id'],
            videoId: $row['video_id'],
            lastWatchedAt: $row['last_watched_at'],
            lastPositionSeconds: (int)($row['last_position_seconds'] ?? 0),
            watchCount: (int)($row['watch_count'] ?? 1),
            context: self::normalizeJsonField($row['context'] ?? []),
            createdAt: $row['created_at'] ?? (new \DateTimeImmutable())->format(DATE_ATOM),
            updatedAt: $row['updated_at'] ?? (new \DateTimeImmutable())->format(DATE_ATOM)
        );
    }

    private static function normalizeJsonField(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
