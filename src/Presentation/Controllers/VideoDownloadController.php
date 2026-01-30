<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Video\VideoDownloadService;
use App\Infrastructure\Http\DownloadServiceException;
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
        } catch (DownloadServiceException $exception) {
            $this->logDownloadFailure($videoId, $exception);
            return JsonResponse::error(
                $response,
                'DOWNLOAD_SERVICE_ERROR',
                'Download service failed',
                [
                    'message' => $exception->getMessage(),
                    'status_code' => $exception->getStatusCode(),
                ],
                502
            );
        } catch (\RuntimeException $exception) {
            $this->logDownloadFailure($videoId, $exception);
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

    private function logDownloadFailure(string $videoId, \Throwable $exception): void
    {
        $context = json_encode([
            'video_id' => $videoId,
            'error' => $exception->getMessage(),
            'type' => get_class($exception),
        ]);
        error_log('[Download] ' . $context);
    }
}
