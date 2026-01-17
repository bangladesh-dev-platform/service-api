<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Domain\Auth\JwtService;
use App\Domain\Auth\PasswordService;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class AuthController
{
    private UserRepository $userRepository;
    private JwtService $jwtService;
    private PasswordService $passwordService;

    public function __construct(
        UserRepository $userRepository,
        JwtService $jwtService,
        PasswordService $passwordService
    ) {
        $this->userRepository = $userRepository;
        $this->jwtService = $jwtService;
        $this->passwordService = $passwordService;
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

        return JsonResponse::success(
            new Response(),
            [
                'user' => $createdUser->toArray(),
                'message' => 'Registration successful. Please verify your email.'
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

        return JsonResponse::success(
            new Response(),
            [
                'user' => $user->toArray(),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwtService->getAccessTokenExpiry(),
            ]
        );
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
}
