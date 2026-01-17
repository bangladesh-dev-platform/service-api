<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Domain\User\UserRepository;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class UserController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get current user profile
     */
    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return JsonResponse::notFound(new Response(), 'User not found');
        }

        return JsonResponse::success(new Response(), $user->toArray());
    }

    /**
     * Update current user profile
     */
    public function updateMe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return JsonResponse::notFound(new Response(), 'User not found');
        }

        // Create updated user (immutable update)
        $updatedUser = new \App\Domain\User\User(
            email: $user->getEmail(),
            passwordHash: $user->getPasswordHash(),
            id: $user->getId(),
            firstName: $data['first_name'] ?? $user->getFirstName(),
            lastName: $data['last_name'] ?? $user->getLastName(),
            phone: $data['phone'] ?? $user->getPhone(),
            avatarUrl: $data['avatar_url'] ?? $user->getAvatarUrl(),
            emailVerified: $user->isEmailVerified(),
            emailVerifiedAt: $user->getEmailVerifiedAt(),
            isActive: $user->isActive(),
            createdAt: $user->getCreatedAt(),
            updatedAt: $user->getUpdatedAt(),
            lastLoginAt: $user->getLastLoginAt(),
            roles: $user->getRoles(),
            permissions: $user->getPermissions()
        );

        $result = $this->userRepository->update($updatedUser);

        return JsonResponse::success(new Response(), $result->toArray());
    }

    /**
     * Get all users (admin endpoint)
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $users = $this->userRepository->findAll($perPage, $offset);
        $total = $this->userRepository->count();

        $data = array_map(fn($user) => $user->toArray(), $users);

        return JsonResponse::success(
            new Response(),
            $data,
            [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage),
            ]
        );
    }

    /**
     * Get user by ID
     */
    public function getById(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $args['id'];

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return JsonResponse::notFound(new Response(), 'User not found');
        }

        return JsonResponse::success(new Response(), $user->toArray());
    }
}
