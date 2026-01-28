<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\User\User;
use App\Domain\User\UserRepository;
use PDO;

class PgUserRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(User $user): User
    {
        $sql = "
            INSERT INTO users (email, password_hash, first_name, last_name, phone, avatar_url, is_active)
            VALUES (:email, :password_hash, :first_name, :last_name, :phone, :avatar_url, :is_active)
            RETURNING *
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'avatar_url' => $user->getAvatarUrl(),
            'is_active' => $user->isActive(),
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Failed to create user');
        }

        return $this->hydrateWithRoles($row);
    }

    public function findById(string $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return $this->hydrateWithRoles($row);
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return $this->hydrateWithRoles($row);
    }

    public function update(User $user): User
    {
        $sql = "
            UPDATE users 
            SET first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                avatar_url = :avatar_url,
                email_verified = :email_verified,
                is_active = :is_active
            WHERE id = :id
            RETURNING *
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $user->getId(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'avatar_url' => $user->getAvatarUrl(),
            'email_verified' => $user->isEmailVerified(),
            'is_active' => $user->isActive(),
        ]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Failed to update user');
        }

        return $this->hydrateWithRoles($row);
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function updateLastLogin(string $id): void
    {
        $sql = "UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public function updatePassword(string $id, string $passwordHash): void
    {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }

    public function markEmailVerified(string $id): void
    {
        $sql = "UPDATE users SET email_verified = true, email_verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public function findAll(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $users = [];
        while ($row = $stmt->fetch()) {
            $users[] = $this->hydrateWithRoles($row);
        }

        return $users;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM users";
        return (int)$this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Hydrate user with roles and permissions
     */
    private function hydrateWithRoles(array $row): User
    {
        $userId = $row['id'];

        // Fetch user roles
        $rolesStmt = $this->pdo->prepare("SELECT role FROM user_roles WHERE user_id = :user_id");
        $rolesStmt->execute(['user_id' => $userId]);
        $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch user permissions
        $permsStmt = $this->pdo->prepare("
            SELECT CASE 
                WHEN resource_type IS NOT NULL THEN CONCAT(resource_type, '.', permission)
                ELSE permission 
            END as permission
            FROM user_permissions 
            WHERE user_id = :user_id
        ");
        $permsStmt->execute(['user_id' => $userId]);
        $permissions = $permsStmt->fetchAll(PDO::FETCH_COLUMN);

        $row['roles'] = $roles;
        $row['permissions'] = $permissions;

        return User::fromArray($row);
    }
}
