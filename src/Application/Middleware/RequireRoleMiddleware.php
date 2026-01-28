<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RequireRoleMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $requiredRoles;

    /** @var string[] */
    private array $requiredPermissions;

    /**
     * @param string[] $requiredRoles
     * @param string[] $requiredPermissions
     */
    public function __construct(array $requiredRoles = [], array $requiredPermissions = [])
    {
        $this->requiredRoles = array_map('strtolower', $requiredRoles);
        $this->requiredPermissions = array_map('strtolower', $requiredPermissions);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $userRoles = array_map(
            'strtolower',
            (array)($request->getAttribute('user_roles') ?? [])
        );
        $userPermissions = array_map(
            'strtolower',
            (array)($request->getAttribute('user_permissions') ?? [])
        );

        if (!$this->isAuthorized($userRoles, $userPermissions)) {
            return JsonResponse::forbidden(new Response(), 'You do not have access to this resource');
        }

        return $handler->handle($request);
    }

    /**
     * @param string[] $userRoles
     * @param string[] $userPermissions
     */
    private function isAuthorized(array $userRoles, array $userPermissions): bool
    {
        if (empty($this->requiredRoles) && empty($this->requiredPermissions)) {
            return true;
        }

        $roleAllowed = true;
        if (!empty($this->requiredRoles)) {
            $roleAllowed = count(array_intersect($userRoles, $this->requiredRoles)) > 0;
        }

        $permissionAllowed = true;
        if (!empty($this->requiredPermissions)) {
            $permissionAllowed = count(array_intersect($userPermissions, $this->requiredPermissions)) > 0;
        }

        return $roleAllowed && $permissionAllowed;
    }
}
