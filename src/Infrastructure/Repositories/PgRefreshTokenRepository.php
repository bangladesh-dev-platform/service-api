<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Auth\RefreshToken;
use App\Domain\Auth\RefreshTokenRepository;
use App\Domain\Auth\PasswordService;
use DateTimeImmutable;
use PDO;

class PgRefreshTokenRepository implements RefreshTokenRepository
{
    public function __construct(
        private PDO $pdo,
        private PasswordService $passwordService,
    ) {
    }

    public function createToken(string $userId, string $plainToken, DateTimeImmutable $expiresAt): RefreshToken
    {
        $hash = $this->passwordService->hashToken($plainToken);

        $sql = '
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
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
            throw new \RuntimeException('Failed to store refresh token');
        }

        return RefreshToken::fromArray($row);
    }

    public function findByToken(string $plainToken): ?RefreshToken
    {
        $hash = $this->passwordService->hashToken($plainToken);

        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE token_hash = :token_hash LIMIT 1');
        $stmt->execute(['token_hash' => $hash]);

        $row = $stmt->fetch();
        return $row ? RefreshToken::fromArray($row) : null;
    }

    public function revokeToken(string $tokenId, ?string $replacedById = null): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE refresh_tokens
            SET revoked_at = CURRENT_TIMESTAMP,
                replaced_by = COALESCE(:replaced_by, replaced_by)
            WHERE id = :id AND revoked_at IS NULL
        ');

        $stmt->execute([
            'id' => $tokenId,
            'replaced_by' => $replacedById,
        ]);
    }

    public function revokeTokensForUser(string $userId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE refresh_tokens
            SET revoked_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id AND revoked_at IS NULL
        ');

        $stmt->execute(['user_id' => $userId]);
    }
}
