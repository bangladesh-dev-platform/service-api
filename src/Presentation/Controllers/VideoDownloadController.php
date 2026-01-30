<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Video\VideoDownloadService;
use App\Shared\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoDownloadController
{
    public function __construct(private VideoDownloadService $downloads)
    {
    }

    public function manifest(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $videoId = (string)($args['id'] ?? '');
        if ($videoId === '') {
            return JsonResponse::validationError($response, [
                'video' => 'Video id is required',
            ]);
        }

        try {
            $manifest = $this->downloads->getManifest($videoId);
        } catch (\RuntimeException $exception) {
            return JsonResponse::error(
                $response,
                'DOWNLOAD_SERVICE_ERROR',
                $exception->getMessage(),
                null,
                502
            );
        }

        if ($manifest === null) {
            return JsonResponse::notFound($response, 'Video not found');
        }

        return JsonResponse::success($response, $manifest);
    }
}
