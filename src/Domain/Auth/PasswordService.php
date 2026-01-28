<?php

declare(strict_types=1);

namespace App\Domain\Auth;

class PasswordService
{
    private int $cost;

    public function __construct(int $cost = 12)
    {
        $this->cost = $cost;
    }

    /**
     * Hash a password using bcrypt
     */
    public function hash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);

        if (!is_string($hash)) {
            throw new \RuntimeException('Failed to hash password');
        }

        return $hash;
    }

    /**
     * Verify a password against a hash
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs rehashing (e.g., cost factor changed)
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    /**
     * Validate password strength
     */
    public function validateStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        return $errors;
    }

    /**
     * Generate a random secure token (for password resets, email verification)
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash a token for storage
     */
    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
