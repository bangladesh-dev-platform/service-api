<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Video;

use App\Domain\Video\Comment;
use App\Domain\Video\CommentRepository;
use PDO;

class PgCommentRepository implements CommentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listComments(string $videoId, ?string $parentId, int $limit, int $offset): array
    {
        $sql = 'SELECT c.*, row_to_json(u) as author
                FROM video_portal.video_comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.video_id = :video_id AND c.parent_id ' . ($parentId ? '= :parent_id' : 'IS NULL') . '
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':video_id', $videoId);
        if ($parentId) {
            $stmt->bindValue(':parent_id', $parentId);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row) {
            if (isset($row['author']) && is_string($row['author'])) {
                $row['author'] = json_decode($row['author'], true) ?? null;
            }
            return Comment::fromArray($row);
        }, $rows);
    }

    public function countComments(string $videoId, ?string $parentId): int
    {
        $sql = 'SELECT COUNT(*) FROM video_portal.video_comments WHERE video_id = :video_id AND parent_id ' . ($parentId ? '= :parent_id' : 'IS NULL');
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':video_id', $videoId);
        if ($parentId) {
            $stmt->bindValue(':parent_id', $parentId);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function create(string $videoId, string $userId, ?string $parentId, string $body): Comment
    {
        $sql = 'INSERT INTO video_portal.video_comments (video_id, user_id, parent_id, body)
                VALUES (:video_id, :user_id, :parent_id, :body)
                RETURNING *';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'video_id' => $videoId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \RuntimeException('Unable to create comment');
        }

        return Comment::fromArray($row);
    }

    public function softDelete(string $commentId, string $userId, bool $isAdmin = false): bool
    {
        $sql = "UPDATE video_portal.video_comments
                SET is_deleted = TRUE,
                    body = CASE WHEN :is_admin THEN body ELSE '[deleted]' END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id " . ($isAdmin ? '' : 'AND user_id = :user_id');

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $commentId);
        $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_BOOL);
        if (!$isAdmin) {
            $stmt->bindValue(':user_id', $userId);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function findById(string $id): ?Comment
    {
        $sql = 'SELECT c.*, row_to_json(u) as author
                FROM video_portal.video_comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if (isset($row['author']) && is_string($row['author'])) {
            $row['author'] = json_decode($row['author'], true) ?? null;
        }

        return Comment::fromArray($row);
    }
}
