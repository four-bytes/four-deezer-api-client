<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\Exception;

use Four\Deezer\Exception\ApiException;
use Four\Deezer\Exception\DeezerException;
use Four\Deezer\Exception\NotFoundException;
use Four\Deezer\Exception\RateLimitException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeezerException::class)]
#[CoversClass(ApiException::class)]
#[CoversClass(RateLimitException::class)]
#[CoversClass(NotFoundException::class)]
final class ExceptionTest extends TestCase
{
    // ---------------------------------------------------------------
    // RateLimitException
    // ---------------------------------------------------------------

    #[Test]
    public function rateLimitExceptionExceededContainsCorrectMessage(): void
    {
        $exception = RateLimitException::exceeded(5000);

        $this->assertSame('Rate limit exceeded. Retry after 5000ms', $exception->getMessage());
    }

    #[Test]
    public function rateLimitExceptionExceededHasCorrectRetryAfterMs(): void
    {
        $exception = RateLimitException::exceeded(5000);

        $this->assertSame(5000, $exception->getRetryAfterMs());
    }

    #[Test]
    public function rateLimitExceptionHasStatusCode429(): void
    {
        $exception = RateLimitException::exceeded(1000);

        $this->assertSame(429, $exception->getStatusCode());
    }

    #[Test]
    public function rateLimitExceptionWithCustomRetryAfter(): void
    {
        $exception = RateLimitException::exceeded(30000);

        $this->assertSame(30000, $exception->getRetryAfterMs());
        $this->assertStringContainsString('30000ms', $exception->getMessage());
    }

    #[Test]
    public function rateLimitExceptionWithDefaultRetryAfter(): void
    {
        $exception = new RateLimitException();

        $this->assertSame(0, $exception->getRetryAfterMs());
        $this->assertSame('Deezer API rate limit exceeded', $exception->getMessage());
    }

    // ---------------------------------------------------------------
    // ApiException
    // ---------------------------------------------------------------

    #[Test]
    public function apiExceptionFromResponseCreatesCorrectException(): void
    {
        $error = ['code' => 800, 'message' => 'Token invalid', 'type' => 'OAuthException'];
        $exception = ApiException::fromResponse($error, '/album/123');

        $this->assertSame('Token invalid', $exception->getMessage());
        $this->assertSame(800, $exception->getErrorCode());
        $this->assertSame('OAuthException', $exception->getErrorType());
        $this->assertSame('/album/123', $exception->getEndpoint());
    }

    #[Test]
    public function apiExceptionFromResponseWithStatusCode(): void
    {
        $error = ['code' => 800, 'message' => 'Token invalid'];
        $exception = ApiException::fromResponse($error, '/album/123');

        // code wird als statusCode UND errorCode gesetzt
        $this->assertSame(800, $exception->getStatusCode());
    }

    #[Test]
    public function apiExceptionFromResponseWithMissingFieldsUsesDefaults(): void
    {
        $exception = ApiException::fromResponse([], '/test');

        $this->assertSame('Unknown API error', $exception->getMessage());
        $this->assertNull($exception->getErrorCode());
        $this->assertNull($exception->getErrorType());
    }

    #[Test]
    public function apiExceptionFromResponseWithoutEndpoint(): void
    {
        $error = ['code' => 200, 'message' => 'Quota limit exceeded'];
        $exception = ApiException::fromResponse($error);

        $this->assertNull($exception->getEndpoint());
    }

    // ---------------------------------------------------------------
    // Exception-Hierarchie
    // ---------------------------------------------------------------

    #[Test]
    public function rateLimitExceptionIsInstanceOfDeezerException(): void
    {
        $exception = RateLimitException::exceeded(5000);

        $this->assertInstanceOf(DeezerException::class, $exception);
    }

    #[Test]
    public function rateLimitExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = RateLimitException::exceeded(5000);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[Test]
    public function apiExceptionIsInstanceOfDeezerException(): void
    {
        $exception = ApiException::fromResponse(['code' => 800, 'message' => 'Error'], '/test');

        $this->assertInstanceOf(DeezerException::class, $exception);
    }

    #[Test]
    public function notFoundExceptionIsInstanceOfDeezerException(): void
    {
        $exception = NotFoundException::forAlbum(123);

        $this->assertInstanceOf(DeezerException::class, $exception);
    }

    // ---------------------------------------------------------------
    // NotFoundException
    // ---------------------------------------------------------------

    #[Test]
    public function notFoundExceptionForAlbumContainsAlbumId(): void
    {
        $exception = NotFoundException::forAlbum(42);

        $this->assertStringContainsString('42', $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame('/album/42', $exception->getEndpoint());
    }

    #[Test]
    public function notFoundExceptionForArtistContainsArtistId(): void
    {
        $exception = NotFoundException::forArtist(1026);

        $this->assertStringContainsString('1026', $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame('/artist/1026', $exception->getEndpoint());
    }

    #[Test]
    public function notFoundExceptionForTrackContainsTrackId(): void
    {
        $exception = NotFoundException::forTrack(789456);

        $this->assertStringContainsString('789456', $exception->getMessage());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame('/track/789456', $exception->getEndpoint());
    }

    // ---------------------------------------------------------------
    // DeezerException
    // ---------------------------------------------------------------

    #[Test]
    public function deezerExceptionStoresEndpoint(): void
    {
        $exception = new DeezerException('Test error', 500, '/test/endpoint');

        $this->assertSame('/test/endpoint', $exception->getEndpoint());
        $this->assertSame(500, $exception->getStatusCode());
    }

    #[Test]
    public function deezerExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('Original error');
        $exception = new DeezerException('Wrapped error', 0, null, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
