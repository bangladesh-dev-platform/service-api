<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Auth\PasswordReset;
use App\Domain\Auth\PasswordResetRepository;
use App\Domain\Auth\PasswordService;
use DateTimeImmutable;
use PDO;

class PgPasswordResetRepository implements PasswordResetRepository
{
    public function __construct(
        private PDO $pdo,
        private PasswordService $passwordService
    ) {
    }

    public function createToken(string $userId, string $plainToken, DateTimeImmutable $expiresAt): PasswordReset
    {
        $hash = $this->passwordService->hashToken($plainToken);

        $sql = '
            INSERT INTO password_resets (user_id, token_hash, expires_at)
            VALUES (:user_id, :token_hash, :expires_at)
            RETURNING *
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => $expiresAt->format('c'),
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Failed to create password reset token');
        }

        return PasswordReset::fromArray($row);
    }

    public function findValidToken(string $plainToken): ?PasswordReset
    {
        $hash = $this->passwordService->hashToken($plainToken);

        $stmt = $this->pdo->prepare('
            SELECT * FROM password_resets
            WHERE token_hash = :token_hash
            ORDER BY created_at DESC
            LIMIT 1
        ');
        $stmt->execute(['token_hash' => $hash]);

        $row = $stmt->fetch();
        return $row ? PasswordReset::fromArray($row) : null;
    }

    public function markUsed(string $tokenId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE password_resets
            SET used_at = CURRENT_TIMESTAMP
            WHERE id = :id AND used_at IS NULL
        ');
        $stmt->execute(['id' => $tokenId]);
    }

    public function cleanupExpiredTokens(): void
    {
        $this->pdo->exec('
            DELETE FROM password_resets
            WHERE (used_at IS NOT NULL) OR (expires_at < CURRENT_TIMESTAMP)
        ');
    }
}
