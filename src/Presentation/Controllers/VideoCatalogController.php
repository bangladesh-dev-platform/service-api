<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Video\VideoCatalogService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoCatalogController
{
    public function __construct(private VideoCatalogService $catalog)
    {
    }

    public function feed(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $limit = $this->normalizeLimit($query['limit'] ?? 12);
        $page = $this->normalizePage($query['page'] ?? 1);
        $category = isset($query['category']) ? trim((string)$query['category']) : null;

        $items = array_map(
            fn($video) => $video->toArray(),
            $this->catalog->getFeed($category ?: null, $page, $limit)
        );

        return JsonResponse::success($response, [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'category' => $category,
        ]);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $video = $this->catalog->getVideo($args['id']);
        if (!$video) {
            return JsonResponse::notFound($response, 'Video not found');
        }

        return JsonResponse::success($response, $video->toArray());
    }

    public function search(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $limit = $this->normalizeLimit($query['limit'] ?? 12);
        $page = $this->normalizePage($query['page'] ?? 1);
        $term = isset($query['q']) ? trim((string)$query['q']) : null;

        $items = array_map(
            fn($video) => $video->toArray(),
            $this->catalog->search($term, $page, $limit)
        );

        return JsonResponse::success($response, [
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'query' => $term,
        ]);
    }

    public function upsert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $request->getParsedBody() ?? [];

        try {
            $video = $this->catalog->upsert(is_array($payload) ? $payload : []);
        } catch (\InvalidArgumentException $exception) {
            return JsonResponse::validationError($response, [
                'video' => $exception->getMessage(),
            ]);
        }

        return JsonResponse::success($response, $video->toArray());
    }

    private function normalizeLimit(mixed $value): int
    {
        $limit = (int)$value;
        if ($limit <= 0) {
            $limit = 12;
        }

        return (int)min(100, max(1, $limit));
    }

    private function normalizePage(mixed $value): int
    {
        $page = (int)$value;
        return max(1, $page);
    }
}
