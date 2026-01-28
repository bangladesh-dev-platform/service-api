<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    public function create(User $user): User;
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function update(User $user): User;
    public function delete(string $id): bool;
    public function updateLastLogin(string $id): void;
    public function updatePassword(string $id, string $passwordHash): void;
    public function findAll(int $limit = 20, int $offset = 0): array;
    public function count(): int;
}
