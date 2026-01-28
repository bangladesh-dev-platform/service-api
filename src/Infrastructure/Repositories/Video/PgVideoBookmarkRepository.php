<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Video;

use App\Domain\Video\Video;
use App\Domain\Video\VideoBookmark;
use App\Domain\Video\VideoBookmarkRepository;
use PDO;

class PgVideoBookmarkRepository implements VideoBookmarkRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $userId, string $videoId, ?string $notes = null): VideoBookmark
    {
        $sql = 'INSERT INTO video_portal.user_video_bookmarks (user_id, video_id, notes)
                VALUES (:user_id, :video_id, :notes)
                ON CONFLICT (user_id, video_id)
                DO UPDATE SET notes = EXCLUDED.notes
                RETURNING *';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoId,
            'notes' => $notes,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException('Unable to create bookmark');
        }

        return VideoBookmark::fromArray($row);
    }

    public function delete(string $userId, string $videoId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM video_portal.user_video_bookmarks WHERE user_id = :user_id AND video_id = :video_id');
        $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoId,
        ]);
    }

    public function listWithVideos(string $userId, int $limit, int $offset): array
    {
        $sql = 'SELECT b.*, row_to_json(v) AS video
                FROM video_portal.user_video_bookmarks b
                JOIN video_portal.videos v ON v.id = b.video_id
                WHERE b.user_id = :user_id
                ORDER BY b.created_at DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row) {
            $videoPayload = is_string($row['video'] ?? null)
                ? json_decode($row['video'], true)
                : ($row['video'] ?? []);

            return [
                'bookmark' => VideoBookmark::fromArray($row),
                'video' => Video::fromArray($videoPayload ?? []),
            ];
        }, $rows);
    }
}
