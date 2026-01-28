<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Video;

use App\Domain\Video\Video;
use App\Domain\Video\VideoRepository;
use PDO;

class PgVideoRepository implements VideoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(string $id): ?Video
    {
        $stmt = $this->pdo->prepare('SELECT * FROM video_portal.videos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findBySource(string $sourceType, string $sourceRef): ?Video
    {
        $stmt = $this->pdo->prepare('SELECT * FROM video_portal.videos WHERE source_type = :type AND source_ref = :ref LIMIT 1');
        $stmt->execute([
            'type' => $sourceType,
            'ref' => $sourceRef,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }

    public function save(Video $video): Video
    {
        $payload = [
            'source_type' => $video->getSourceType(),
            'source_ref' => $video->getSourceRef(),
            'title' => $video->getTitle(),
            'description' => $video->getDescription(),
            'channel_name' => $video->getChannelName(),
            'duration_seconds' => $video->getDurationSeconds(),
            'thumbnail_url' => $video->getThumbnailUrl(),
            'status' => $video->getStatus(),
            'visibility' => $video->getVisibility(),
            'tags' => json_encode($video->getTags(), JSON_THROW_ON_ERROR),
            'metadata' => json_encode($video->getMetadata(), JSON_THROW_ON_ERROR),
        ];

        if ($video->getId()) {
            $payload['id'] = $video->getId();
            $sql = 'UPDATE video_portal.videos
                    SET source_type = :source_type,
                        source_ref = :source_ref,
                        title = :title,
                        description = :description,
                        channel_name = :channel_name,
                        duration_seconds = :duration_seconds,
                        thumbnail_url = :thumbnail_url,
                        status = :status,
                        visibility = :visibility,
                        tags = :tags::jsonb,
                        metadata = :metadata::jsonb,
                        cached_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    RETURNING *';
        } else {
            $sql = 'INSERT INTO video_portal.videos (
                        source_type, source_ref, title, description, channel_name,
                        duration_seconds, thumbnail_url, status, visibility, tags, metadata
                    ) VALUES (
                        :source_type, :source_ref, :title, :description, :channel_name,
                        :duration_seconds, :thumbnail_url, :status, :visibility,
                        :tags::jsonb, :metadata::jsonb
                    ) RETURNING *';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payload);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException('Unable to persist video record');
        }

        return $this->hydrate($row);
    }

    public function getFeed(?string $categorySlug, int $limit, int $offset): array
    {
        if ($categorySlug) {
            $sql = 'SELECT v.*
                    FROM video_portal.videos v
                    JOIN video_portal.video_category_assignments a ON a.video_id = v.id
                    JOIN video_portal.video_categories c ON c.id = a.category_id
                    WHERE c.slug = :category
                    ORDER BY COALESCE(v.cached_at, v.created_at) DESC
                    LIMIT :limit OFFSET :offset';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':category', $categorySlug);
        } else {
            $sql = 'SELECT * FROM video_portal.videos
                    ORDER BY COALESCE(cached_at, created_at) DESC
                    LIMIT :limit OFFSET :offset';
            $stmt = $this->pdo->prepare($sql);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function search(?string $query, int $limit, int $offset): array
    {
        $searchTerm = $query ? '%' . $query . '%' : null;
        $sql = 'SELECT * FROM video_portal.videos';

        if ($searchTerm) {
            $sql .= ' WHERE title ILIKE :query OR description ILIKE :query';
        }

        $sql .= ' ORDER BY COALESCE(cached_at, created_at) DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        if ($searchTerm) {
            $stmt->bindValue(':query', $searchTerm);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Video
    {
        if (isset($row['tags']) && is_string($row['tags'])) {
            $row['tags'] = json_decode($row['tags'], true) ?? [];
        }

        if (isset($row['metadata']) && is_string($row['metadata'])) {
            $row['metadata'] = json_decode($row['metadata'], true) ?? [];
        }

        return Video::fromArray($row);
    }
}
