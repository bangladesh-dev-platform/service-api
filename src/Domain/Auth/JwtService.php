<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private string $algorithm;
    private string $issuer;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->algorithm = $config['algorithm'];
        $this->issuer = $config['issuer'];
        $this->accessTokenExpiry = $config['access_token_expiry'];
        $this->refreshTokenExpiry = $config['refresh_token_expiry'];
    }

    /**
     * Generate access token
     */
    public function generateAccessToken(array $userData): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'sub' => $userData['id'],
            'iat' => $now,
            'exp' => $now + $this->accessTokenExpiry,
            'email' => $userData['email'],
            'roles' => $userData['roles'] ?? [],
            'permissions' => $userData['permissions'] ?? [],
            'type' => 'access'
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Generate refresh token
     */
    public function generateRefreshToken(string $userId): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->refreshTokenExpiry,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16))
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Validate and decode token
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Decode token without validation (for debugging)
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(object $decodedToken): bool
    {
        return isset($decodedToken->exp) && $decodedToken->exp < time();
    }

    /**
     * Get token expiry time
     */
    public function getAccessTokenExpiry(): int
    {
        return $this->accessTokenExpiry;
    }

    public function getRefreshTokenExpiry(): int
    {
        return $this->refreshTokenExpiry;
    }
}
