<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Video;

use App\Domain\Video\Video;
use App\Domain\Video\VideoHistoryEntry;
use App\Domain\Video\VideoHistoryRepository;
use PDO;

class PgVideoHistoryRepository implements VideoHistoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function record(string $userId, string $videoId, ?int $positionSeconds = null, array $context = []): VideoHistoryEntry
    {
        $sql = 'INSERT INTO video_portal.user_video_history (
                    user_id, video_id, last_position_seconds, context
                ) VALUES (
                    :user_id, :video_id, :position, :context
                )
                ON CONFLICT (user_id, video_id)
                DO UPDATE SET
                    last_watched_at = CURRENT_TIMESTAMP,
                    last_position_seconds = COALESCE(EXCLUDED.last_position_seconds, video_portal.user_video_history.last_position_seconds),
                    watch_count = video_portal.user_video_history.watch_count + 1,
                    context = COALESCE(EXCLUDED.context, video_portal.user_video_history.context),
                    updated_at = CURRENT_TIMESTAMP
                RETURNING *';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoId,
            'position' => $positionSeconds,
            'context' => json_encode($context, JSON_THROW_ON_ERROR),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException('Unable to record history entry');
        }

        return VideoHistoryEntry::fromArray($row);
    }

    public function listWithVideos(string $userId, int $limit, int $offset): array
    {
        $sql = 'SELECT h.*, row_to_json(v) AS video
                FROM video_portal.user_video_history h
                JOIN video_portal.videos v ON v.id = h.video_id
                WHERE h.user_id = :user_id
                ORDER BY h.last_watched_at DESC
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
                'history' => VideoHistoryEntry::fromArray($row),
                'video' => Video::fromArray($videoPayload ?? []),
            ];
        }, $rows);
    }
}
