<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use DateTimeImmutable;

class RefreshToken
{
    public function __construct(
        private string $id,
        private string $userId,
        private string $tokenHash,
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
        private ?string $replacedBy,
        private DateTimeImmutable $createdAt,
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

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReplacedBy(): ?string
    {
        return $this->replacedBy;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(DateTimeImmutable $comparisonDate = null): bool
    {
        $comparisonDate = $comparisonDate ?? new DateTimeImmutable('now');
        return $this->expiresAt <= $comparisonDate;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: $row['id'],
            userId: $row['user_id'],
            tokenHash: $row['token_hash'],
            expiresAt: new DateTimeImmutable($row['expires_at']),
            revokedAt: isset($row['revoked_at']) && $row['revoked_at'] !== null
                ? new DateTimeImmutable($row['revoked_at'])
                : null,
            replacedBy: $row['replaced_by'] ?? null,
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
