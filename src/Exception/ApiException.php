<?php

declare(strict_types=1);

namespace Four\Deezer\Exception;

/**
 * Exception thrown for general API errors
 */
class ApiException extends DeezerException
{
    public function __construct(
        string $message,
        int $statusCode = 0,
        ?string $endpoint = null,
        private readonly ?int $errorCode = null,
        private readonly ?string $errorType = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $endpoint, $previous);
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Create from Deezer API error response
     *
     * @param array<string, mixed> $error
     */
    public static function fromResponse(array $error, ?string $endpoint = null): self
    {
        return new self(
            message: $error['message'] ?? 'Unknown API error',
            statusCode: $error['code'] ?? 0,
            endpoint: $endpoint,
            errorCode: $error['code'] ?? null,
            errorType: $error['type'] ?? null
        );
    }
}
