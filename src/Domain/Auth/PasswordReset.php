<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

class PasswordReset
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $tokenHash,
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $usedAt,
        private DateTimeImmutable $createdAt
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

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function isExpired(DateTimeImmutable $comparison = null): bool
    {
        $comparison = $comparison ?? new DateTimeImmutable('now');
        return $this->expiresAt <= $comparison;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            userId: $row['user_id'],
            tokenHash: $row['token_hash'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            usedAt: isset($row['used_at']) && $row['used_at'] !== null
                ? new DateTimeImmutable($row['used_at'])
                : null,
            createdAt: new DateTimeImmutable($row['created_at'])
        );
    }
}
