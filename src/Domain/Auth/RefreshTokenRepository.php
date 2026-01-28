<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

interface RefreshTokenRepository
{
    public function createToken(string $userId, string $plainToken, DateTimeImmutable $expiresAt): RefreshToken;

    public function findByToken(string $plainToken): ?RefreshToken;

    public function revokeToken(string $tokenId, ?string $replacedById = null): void;

    public function revokeTokensForUser(string $userId): void;
}
