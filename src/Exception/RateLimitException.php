<?php

declare(strict_types=1);

namespace Four\Deezer\Exception;

/**
 * Exception thrown when rate limit is exceeded
 */
class RateLimitException extends DeezerException
{
    public function __construct(
        string $message = 'Deezer API rate limit exceeded',
        private readonly int $retryAfterMs = 0,
        ?string $endpoint = null
    ) {
        parent::__construct($message, 429, $endpoint);
    }

    public function getRetryAfterMs(): int
    {
        return $this->retryAfterMs;
    }

    public static function exceeded(int $retryAfterMs = 5000): self
    {
        return new self(
            message: "Rate limit exceeded. Retry after {$retryAfterMs}ms",
            retryAfterMs: $retryAfterMs
        );
    }
}
