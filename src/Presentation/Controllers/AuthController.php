<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Domain\Auth\JwtService;
use App\Domain\Auth\PasswordService;
use App\Domain\Auth\RefreshTokenRepository;
use App\Domain\Auth\PasswordResetRepository;
use App\Domain\Notification\MailService;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Shared\Response\JsonResponse;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class AuthController
{
    private UserRepository $userRepository;
    private JwtService $jwtService;
    private PasswordService $passwordService;
    private RefreshTokenRepository $refreshTokenRepository;
    private PasswordResetRepository $passwordResetRepository;
    private MailService $mailService;
    private int $passwordResetExpirySeconds = 3600; // 1 hour
    private bool $exposeResetToken;

    public function __construct(
        UserRepository $userRepository,
        JwtService $jwtService,
        PasswordService $passwordService,
        RefreshTokenRepository $refreshTokenRepository,
        PasswordResetRepository $passwordResetRepository,
        MailService $mailService,
        array $appConfig
    ) {
        $this->userRepository = $userRepository;
        $this->jwtService = $jwtService;
        $this->passwordService = $passwordService;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->mailService = $mailService;
        $this->exposeResetToken = ($appConfig['env'] ?? 'production') !== 'production';
    }

    /**
     * Register a new user
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate input
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            return JsonResponse::validationError(new Response(), $errors);
        }

        // Check if email already exists
        if ($this->userRepository->findByEmail($data['email'])) {
            return JsonResponse::error(
                new Response(),
                'EMAIL_EXISTS',
                'Email already registered',
                null,
                409
            );
        }

        // Validate password strength
        $passwordErrors = $this->passwordService->validateStrength($data['password']);
        if (!empty($passwordErrors)) {
            return JsonResponse::validationError(
                new Response(),
                ['password' => $passwordErrors]
            );
        }

        // Create user
        $user = new User(
            email: $data['email'],
            passwordHash: $this->passwordService->hash($data['password']),
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null
        );

        $createdUser = $this->userRepository->create($user);

        $verificationSent = false;
        try {
            $this->sendVerificationEmail($createdUser);
            $verificationSent = true;
        } catch (\Throwable $e) {
            // swallow to avoid blocking registration
        }

        return JsonResponse::success(
            new Response(),
            [
                'user' => $createdUser->toArray(),
                'message' => 'Registration successful. Please verify your email.',
                'verification_email_sent' => $verificationSent,
            ],
            [],
            201
        );
    }

    /**
     * Login user
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            return JsonResponse::validationError(
                new Response(),
                ['email' => 'Email and password are required']
            );
        }

        // Find user
        $user = $this->userRepository->findByEmail($data['email']);
        if (!$user) {
            return JsonResponse::error(
                new Response(),
                'INVALID_CREDENTIALS',
                'Invalid email or password',
                null,
                401
            );
        }

        // Verify password
        if (!$this->passwordService->verify($data['password'], $user->getPasswordHash())) {
            return JsonResponse::error(
                new Response(),
                'INVALID_CREDENTIALS',
                'Invalid email or password',
                null,
                401
            );
        }

        // Check if user is active
        if (!$user->isActive()) {
            return JsonResponse::error(
                new Response(),
                'ACCOUNT_INACTIVE',
                'Account is inactive',
                null,
                403
            );
        }

        // Update last login
        $this->userRepository->updateLastLogin($user->getId());

        // Generate tokens
        $accessToken = $this->jwtService->generateAccessToken([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'permissions' => $user->getPermissions(),
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user->getId());
        $this->refreshTokenRepository->createToken(
            $user->getId(),
            $refreshToken,
            $this->calculateExpiryDate($this->jwtService->getRefreshTokenExpiry())
        );

        return JsonResponse::success(
            new Response(),
            $this->buildTokenResponse($user, $accessToken, $refreshToken)
        );
    }

    /**
     * Refresh access token using a valid refresh token
     */
    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $token = $data['refresh_token'] ?? '';

        if (empty($token)) {
            return JsonResponse::validationError(
                new Response(),
                ['refresh_token' => 'Refresh token is required']
            );
        }

        $decoded = $this->jwtService->validateToken($token);
        if ($decoded === null || ($decoded->type ?? '') !== 'refresh') {
            return JsonResponse::unauthorized(new Response(), 'Invalid refresh token');
        }

        $storedToken = $this->refreshTokenRepository->findByToken($token);
        if (!$storedToken || $storedToken->isRevoked() || $storedToken->isExpired()) {
            return JsonResponse::unauthorized(new Response(), 'Refresh token expired or revoked');
        }

        $user = $this->userRepository->findById($storedToken->getUserId());
        if (!$user || !$user->isActive()) {
            return JsonResponse::unauthorized(new Response(), 'User not found or inactive');
        }

        $accessToken = $this->jwtService->generateAccessToken([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'permissions' => $user->getPermissions(),
        ]);

        $newRefreshToken = $this->jwtService->generateRefreshToken($user->getId());
        $newRefreshEntity = $this->refreshTokenRepository->createToken(
            $user->getId(),
            $newRefreshToken,
            $this->calculateExpiryDate($this->jwtService->getRefreshTokenExpiry())
        );

        $this->refreshTokenRepository->revokeToken($storedToken->getId(), $newRefreshEntity->getId());

        return JsonResponse::success(
            new Response(),
            $this->buildTokenResponse($user, $accessToken, $newRefreshToken)
        );
    }

    /**
     * Revoke refresh token (logout)
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $token = $data['refresh_token'] ?? '';

        if (empty($token)) {
            return JsonResponse::validationError(
                new Response(),
                ['refresh_token' => 'Refresh token is required']
            );
        }

        $storedToken = $this->refreshTokenRepository->findByToken($token);
        if ($storedToken) {
            $this->refreshTokenRepository->revokeToken($storedToken->getId());
        }

        return JsonResponse::success(new Response(), ['message' => 'Logged out successfully']);
    }

    /**
     * Initiate password reset flow
     */
    public function forgotPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $email = trim((string)($data['email'] ?? ''));

        if (empty($email)) {
            return JsonResponse::validationError(new Response(), ['email' => 'Email is required']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return JsonResponse::validationError(new Response(), ['email' => 'Invalid email format']);
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            // Avoid leaking which emails exist
            return JsonResponse::success(new Response(), [
                'message' => 'If the account exists, a reset link has been generated.'
            ]);
        }

        $token = $this->passwordService->generateToken(16);
        $this->passwordResetRepository->createToken(
            $user->getId(),
            $token,
            $this->calculateExpiryDate($this->passwordResetExpirySeconds)
        );

        $this->passwordResetRepository->cleanupExpiredTokens();

        try {
            $this->mailService->sendPasswordReset($user->getEmail(), $token, $user->getFullName());
        } catch (\Throwable $e) {
            return JsonResponse::error(
                new Response(),
                'MAIL_SEND_FAILED',
                'Unable to send password reset email',
                null,
                500
            );
        }

        $payload = [
            'message' => 'Password reset instructions sent to your email.',
        ];

        if ($this->exposeResetToken) {
            $payload['reset_token'] = $token;
            $payload['expires_in'] = $this->passwordResetExpirySeconds;
        }

        return JsonResponse::success(new Response(), $payload);
    }

    /**
     * Resend email verification (auth required)
     */
    public function resendVerification(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized(new Response());
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return JsonResponse::unauthorized(new Response());
        }

        if ($user->isEmailVerified()) {
            return JsonResponse::success(new Response(), [
                'message' => 'Email already verified',
            ]);
        }

        try {
            $this->sendVerificationEmail($user);
        } catch (\Throwable $e) {
            return JsonResponse::error(
                new Response(),
                'MAIL_SEND_FAILED',
                'Unable to send verification email',
                null,
                500
            );
        }

        return JsonResponse::success(new Response(), [
            'message' => 'Verification email sent successfully',
        ]);
    }

    /**
     * Verify email using token
     */
    public function verifyEmail(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $token = $data['token'] ?? '';

        if (empty($token)) {
            return JsonResponse::validationError(new Response(), ['token' => 'Verification token is required']);
        }

        $decoded = $this->jwtService->validateToken($token);
        if ($decoded === null || ($decoded->type ?? '') !== 'verify') {
            return JsonResponse::unauthorized(new Response(), 'Invalid or expired verification token');
        }

        $user = $this->userRepository->findById($decoded->sub ?? '');
        if (!$user) {
            return JsonResponse::unauthorized(new Response(), 'User not found');
        }

        if (strcasecmp($user->getEmail(), $decoded->email ?? '') !== 0) {
            return JsonResponse::unauthorized(new Response(), 'Token does not match this user');
        }

        if ($user->isEmailVerified()) {
            return JsonResponse::success(new Response(), [
                'message' => 'Email already verified',
            ]);
        }

        $this->userRepository->markEmailVerified($user->getId());

        return JsonResponse::success(new Response(), [
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Change password (auth required)
     */
    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized(new Response());
        }

        $data = $request->getParsedBody() ?? [];
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? $newPassword);

        $errors = [];
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required';
        }

        if (empty($newPassword)) {
            $errors['new_password'] = 'New password is required';
        } else {
            $passwordErrors = $this->passwordService->validateStrength($newPassword);
            if (!empty($passwordErrors)) {
                $errors['new_password'] = $passwordErrors;
            }
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            return JsonResponse::validationError(new Response(), $errors);
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return JsonResponse::unauthorized(new Response(), 'User not found');
        }

        if (!$this->passwordService->verify($currentPassword, $user->getPasswordHash())) {
            return JsonResponse::validationError(new Response(), [
                'current_password' => 'Current password is incorrect'
            ]);
        }

        if ($currentPassword === $newPassword) {
            return JsonResponse::validationError(new Response(), [
                'new_password' => 'New password must be different from current password'
            ]);
        }

        $hashed = $this->passwordService->hash($newPassword);
        $this->userRepository->updatePassword($user->getId(), $hashed);
        $this->refreshTokenRepository->revokeTokensForUser($user->getId());

        return JsonResponse::success(new Response(), [
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Complete password reset
     */
    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody() ?? [];
        $token = $data['token'] ?? '';
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? $password);

        if (empty($token)) {
            return JsonResponse::validationError(new Response(), ['token' => 'Reset token is required']);
        }

        if ($password !== $confirmPassword) {
            return JsonResponse::validationError(new Response(), ['confirm_password' => 'Passwords do not match']);
        }

        $passwordErrors = $this->passwordService->validateStrength($password);
        if (!empty($passwordErrors)) {
            return JsonResponse::validationError(new Response(), ['password' => $passwordErrors]);
        }

        $resetRecord = $this->passwordResetRepository->findValidToken($token);
        if (!$resetRecord || $resetRecord->isUsed() || $resetRecord->isExpired()) {
            return JsonResponse::unauthorized(new Response(), 'Invalid or expired reset token');
        }

        $user = $this->userRepository->findById($resetRecord->getUserId());
        if (!$user) {
            return JsonResponse::unauthorized(new Response(), 'User no longer exists');
        }

        $hashed = $this->passwordService->hash($password);
        $this->userRepository->updatePassword($user->getId(), $hashed);

        $this->passwordResetRepository->markUsed($resetRecord->getId());
        $this->refreshTokenRepository->revokeTokensForUser($user->getId());

        return JsonResponse::success(new Response(), [
            'message' => 'Password has been reset successfully'
        ]);
    }

    /**
     * Validate registration data
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
    }

    private function buildTokenResponse(User $user, string $accessToken, string $refreshToken): array
    {
        return [
            'user' => $user->toArray(),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtService->getAccessTokenExpiry(),
        ];
    }

    private function calculateExpiryDate(int $seconds): DateTimeImmutable
    {
        return (new DateTimeImmutable('now'))->modify(sprintf('+%d seconds', $seconds));
    }

    private function sendVerificationEmail(User $user): void
    {
        $token = $this->jwtService->generateEmailVerificationToken($user->getId(), $user->getEmail());
        $this->mailService->sendEmailVerification($user->getEmail(), $token, $user->getFullName());
    }
}
