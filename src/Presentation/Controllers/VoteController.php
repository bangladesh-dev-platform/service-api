<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Vote\VoteService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VoteController
{
    public function __construct(private VoteService $service)
    {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getOverview($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function elections(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponse::success($response, [
            'items' => $this->service->getElections(),
        ]);
    }

    public function parties(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getParties($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function regions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getRegions($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function timeline(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getTimeline($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function candidates(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getCandidates($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function resources(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $electionId = isset($query['election_id']) ? trim((string)$query['election_id']) : null;

        $payload = $this->service->getResources($electionId ?: null);
        if (!$payload) {
            return JsonResponse::notFound($response, 'Election not found');
        }

        return JsonResponse::success($response, $payload);
    }

    public function methodology(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return JsonResponse::success($response, $this->service->getMethodology());
    }
}
