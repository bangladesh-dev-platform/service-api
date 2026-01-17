<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domain\Auth\JwtService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JwtAuthMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return JsonResponse::unauthorized(
                new Response(),
                'Authorization header missing'
            );
        }

        // Extract token from "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return JsonResponse::unauthorized(
                new Response(),
                'Invalid authorization header format'
            );
        }

        $token = $matches[1];
        $decoded = $this->jwtService->validateToken($token);

        if ($decoded === null) {
            return JsonResponse::unauthorized(
                new Response(),
                'Invalid or expired token'
            );
        }

        // Check token type
        if (!isset($decoded->type) || $decoded->type !== 'access') {
            return JsonResponse::unauthorized(
                new Response(),
                'Invalid token type'
            );
        }

        // Add user info to request attributes
        $request = $request->withAttribute('user_id', $decoded->sub);
        $request = $request->withAttribute('user_email', $decoded->email ?? null);
        $request = $request->withAttribute('user_roles', $decoded->roles ?? []);
        $request = $request->withAttribute('user_permissions', $decoded->permissions ?? []);

        return $handler->handle($request);
    }
}
