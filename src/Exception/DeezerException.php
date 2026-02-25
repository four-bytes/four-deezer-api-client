<?php

declare(strict_types=1);

namespace Four\Deezer\Exception;

/**
 * Base exception for all Deezer API errors
 */
class DeezerException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly ?string $endpoint = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }
}
