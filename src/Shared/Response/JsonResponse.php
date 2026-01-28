<?php

declare(strict_types=1);

namespace App\Shared\Response;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class JsonResponse
{
    /**
     * Success response
     */
    public static function success(
        ResponseInterface $response,
        $data = null,
        array $meta = [],
        int $statusCode = 200
    ): ResponseInterface {
        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge(['timestamp' => date('c')], $meta)
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        ResponseInterface $response,
        string $code,
        string $message,
        $details = null,
        int $statusCode = 400
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'meta' => ['timestamp' => date('c')]
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        ResponseInterface $response,
        array $errors
    ): ResponseInterface {
        return self::error(
            $response,
            'VALIDATION_ERROR',
            'Validation failed',
            $errors,
            422
        );
    }

    /**
     * Not found response
     */
    public static function notFound(
        ResponseInterface $response,
        string $message = 'Resource not found'
    ): ResponseInterface {
        return self::error(
            $response,
            'NOT_FOUND',
            $message,
            null,
            404
        );
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        ResponseInterface $response,
        string $message = 'Authentication required'
    ): ResponseInterface {
        return self::error(
            $response,
            'UNAUTHORIZED',
            $message,
            null,
            401
        );
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        ResponseInterface $response,
        string $message = 'Access forbidden'
    ): ResponseInterface {
        return self::error(
            $response,
            'FORBIDDEN',
            $message,
            null,
            403
        );
    }
}
