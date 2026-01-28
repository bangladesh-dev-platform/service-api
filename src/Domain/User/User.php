<?php

declare(strict_types=1);

namespace App\Domain\User;

class User
{
    private ?string $id;
    private string $email;
    private string $passwordHash;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $phone;
    private ?string $avatarUrl;
    private bool $emailVerified;
    private ?string $emailVerifiedAt;
    private bool $isActive;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $lastLoginAt;
    private array $roles;
    private array $permissions;

    public function __construct(
        string $email,
        string $passwordHash,
        ?string $id = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $avatarUrl = null,
        bool $emailVerified = false,
        ?string $emailVerifiedAt = null,
        bool $isActive = true,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $lastLoginAt = null,
        array $roles = [],
        array $permissions = []
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->avatarUrl = $avatarUrl;
        $this->emailVerified = $emailVerified;
        $this->emailVerifiedAt = $emailVerifiedAt;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->lastLoginAt = $lastLoginAt;
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    // Getters
    public function getId(): ?string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function isEmailVerified(): bool { return $this->emailVerified; }
    public function getEmailVerifiedAt(): ?string { return $this->emailVerifiedAt; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function getLastLoginAt(): ?string { return $this->lastLoginAt; }
    public function getRoles(): array { return $this->roles; }
    public function getPermissions(): array { return $this->permissions; }

    public function getFullName(): string
    {
        if ($this->firstName && $this->lastName) {
            return $this->firstName . ' ' . $this->lastName;
        }
        return $this->firstName ?? $this->lastName ?? $this->email;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Convert to array (for API responses)
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl,
            'email_verified' => $this->emailVerified,
            'email_verified_at' => $this->emailVerifiedAt,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'last_login_at' => $this->lastLoginAt,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];

        if ($includePassword) {
            $data['password_hash'] = $this->passwordHash;
        }

        return $data;
    }

    /**
     * Create from database row
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            passwordHash: $data['password_hash'],
            id: $data['id'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            emailVerified: (bool)($data['email_verified'] ?? false),
            emailVerifiedAt: $data['email_verified_at'] ?? null,
            isActive: (bool)($data['is_active'] ?? true),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            lastLoginAt: $data['last_login_at'] ?? null,
            roles: $data['roles'] ?? [],
            permissions: $data['permissions'] ?? []
        );
    }
}
