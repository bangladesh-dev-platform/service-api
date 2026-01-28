<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Video\VideoEngagementService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoEngagementController
{
    public function __construct(private VideoEngagementService $service)
    {
    }

    public function listBookmarks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $query = $request->getQueryParams();
        $limit = $this->normalizeLimit($query['limit'] ?? 20);
        $page = $this->normalizePage($query['page'] ?? 1);

        $records = $this->service->listBookmarks($userId, $page, $limit);
        $items = array_map(fn(array $row) => [
            'bookmark' => $row['bookmark']->toArray(),
            'video' => $row['video']->toArray(),
        ], $records);

        return JsonResponse::success($response, [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function createBookmark(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $payload = $request->getParsedBody() ?? [];
        $videoId = trim((string)($payload['video_id'] ?? ''));
        $notes = isset($payload['notes']) ? trim((string)$payload['notes']) : null;

        if ($videoId === '') {
            return JsonResponse::validationError($response, ['video_id' => 'video_id is required']);
        }

        try {
            $result = $this->service->createBookmark($userId, $videoId, $notes ?: null);
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::notFound($response, $exception->getMessage());
        }

        return JsonResponse::success($response, [
            'bookmark' => $result['bookmark']->toArray(),
            'video' => $result['video']->toArray(),
        ]);
    }

    public function deleteBookmark(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $videoId = $args['videoId'] ?? null;
        if (!$videoId) {
            return JsonResponse::validationError($response, ['video_id' => 'video id missing']);
        }

        $this->service->deleteBookmark($userId, $videoId);
        return JsonResponse::success($response, ['message' => 'Bookmark removed']);
    }

    public function listHistory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $query = $request->getQueryParams();
        $limit = $this->normalizeLimit($query['limit'] ?? 20);
        $page = $this->normalizePage($query['page'] ?? 1);

        $records = $this->service->listHistory($userId, $page, $limit);
        $items = array_map(fn(array $row) => [
            'history' => $row['history']->toArray(),
            'video' => $row['video']->toArray(),
        ], $records);

        return JsonResponse::success($response, [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function recordHistory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            return JsonResponse::unauthorized($response);
        }

        $payload = $request->getParsedBody() ?? [];
        $videoId = trim((string)($payload['video_id'] ?? ''));
        $position = isset($payload['position_seconds']) ? (int)$payload['position_seconds'] : null;
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        if ($videoId === '') {
            return JsonResponse::validationError($response, ['video_id' => 'video_id is required']);
        }

        try {
            $result = $this->service->recordHistory($userId, $videoId, $position, $context);
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::notFound($response, $exception->getMessage());
        }

        return JsonResponse::success($response, [
            'history' => $result['history']->toArray(),
            'video' => $result['video']->toArray(),
        ]);
    }

    private function normalizeLimit(mixed $value): int
    {
        $limit = (int)$value;
        if ($limit <= 0) {
            $limit = 20;
        }

        return (int)min(100, max(1, $limit));
    }

    private function normalizePage(mixed $value): int
    {
        $page = (int)$value;
        return max(1, $page);
    }
}
