<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

interface PasswordResetRepository
{
    public function createToken(string $userId, string $plainToken, DateTimeImmutable $expiresAt): PasswordReset;

    public function findValidToken(string $plainToken): ?PasswordReset;

    public function markUsed(string $tokenId): void;

    public function cleanupExpiredTokens(): void;
}
