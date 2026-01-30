<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

class DownloadServiceException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $responseBody = null,
        private readonly ?array $decodedBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function networkError(string $message, ?\Throwable $previous = null): self
    {
        return new self($message ?: 'Download service unreachable', 0, null, null, $previous);
    }

    public static function httpError(string $message, int $statusCode, string $body, ?array $decoded): self
    {
        return new self($message ?: 'Download service error', $statusCode, $body, $decoded);
    }

    public static function invalidPayload(string $body): self
    {
        return new self('Invalid response from download service', 0, $body);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function getDecodedBody(): ?array
    {
        return $this->decodedBody;
    }
}
