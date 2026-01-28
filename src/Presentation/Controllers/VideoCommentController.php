<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Video\CommentService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoCommentController
{
    public function __construct(private CommentService $comments)
    {
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int)($query['page'] ?? 1));
        $limit = min(100, max(1, (int)($query['limit'] ?? 20)));
        $parentId = isset($query['parent_id']) ? trim((string)$query['parent_id']) : null;

        try {
            $items = $this->comments->list($args['id'], $parentId ?: null, $page, $limit);
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::notFound($response, $exception->getMessage());
        }

        return JsonResponse::success($response, [
            'items' => array_map(fn ($comment) => $comment->toArray(), $items),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $payload = $request->getParsedBody() ?? [];
        $body = trim((string)($payload['text'] ?? ''));
        $parentId = isset($payload['parent_id']) ? trim((string)$payload['parent_id']) : null;

        if ($body === '') {
            return JsonResponse::validationError($response, ['text' => 'Comment text is required']);
        }

        try {
            $comment = $this->comments->create($args['id'], $userId, $parentId ?: null, $body);
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::validationError($response, ['comment' => $exception->getMessage()]);
        }

        return JsonResponse::success($response, $comment->toArray(), [], 201);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $roles = array_map('strtolower', (array)$request->getAttribute('user_roles', []));
        $isAdmin = in_array('admin', $roles, true);

        $deleted = $this->comments->delete($args['commentId'], $userId, $isAdmin);

        if (!$deleted) {
            return JsonResponse::forbidden($response, 'Unable to delete comment');
        }

        return JsonResponse::success($response, ['message' => 'Comment removed']);
    }
}
