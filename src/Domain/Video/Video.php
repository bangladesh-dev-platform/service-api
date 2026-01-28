<?php

declare(strict_types=1);

namespace App\Domain\Video;

class Video
{
    public function __construct(
        private ?string $id,
        private string $sourceType,
        private string $sourceRef,
        private string $title,
        private ?string $description = null,
        private ?string $channelName = null,
        private ?int $durationSeconds = null,
        private ?string $thumbnailUrl = null,
        private string $status = 'published',
        private string $visibility = 'public',
        private array $tags = [],
        private array $metadata = [],
        private ?string $cachedAt = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getSourceRef(): string
    {
        return $this->sourceRef;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getChannelName(): ?string
    {
        return $this->channelName;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCachedAt(): ?string
    {
        return $this->cachedAt;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withTimestamps(?string $createdAt, ?string $updatedAt, ?string $cachedAt = null): self
    {
        $clone = clone $this;
        $clone->createdAt = $createdAt;
        $clone->updatedAt = $updatedAt;
        $clone->cachedAt = $cachedAt;
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source_type' => $this->sourceType,
            'source_ref' => $this->sourceRef,
            'title' => $this->title,
            'description' => $this->description,
            'channel_name' => $this->channelName,
            'duration_seconds' => $this->durationSeconds,
            'thumbnail_url' => $this->thumbnailUrl,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'cached_at' => $this->cachedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'] ?? null,
            sourceType: $row['source_type'] ?? 'youtube',
            sourceRef: $row['source_ref'] ?? '',
            title: $row['title'] ?? '',
            description: $row['description'] ?? null,
            channelName: $row['channel_name'] ?? null,
            durationSeconds: isset($row['duration_seconds']) ? (int)$row['duration_seconds'] : null,
            thumbnailUrl: $row['thumbnail_url'] ?? null,
            status: $row['status'] ?? 'published',
            visibility: $row['visibility'] ?? 'public',
            tags: self::normalizeJsonField($row['tags'] ?? []),
            metadata: self::normalizeJsonField($row['metadata'] ?? []),
            cachedAt: $row['cached_at'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
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
