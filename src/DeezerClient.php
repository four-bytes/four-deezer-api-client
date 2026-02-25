<?php

declare(strict_types=1);

namespace Four\Deezer;

use Four\Deezer\Endpoint\AlbumEndpoint;
use Four\Deezer\Endpoint\ArtistEndpoint;
use Four\Deezer\Endpoint\EditorialEndpoint;
use Four\Deezer\Endpoint\PlaylistEndpoint;
use Four\Deezer\Exception\ApiException;
use Four\Deezer\Exception\DeezerException;
use Four\Deezer\Exception\RateLimitException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Deezer API Client
 *
 * A modern, PSR-compliant client for the Deezer Public API.
 * Supports rate limiting (via four-rate-limiting) and caching (PSR-6).
 *
 * @example
 * ```php
 * // Simple usage with auto-discovery
 * $client = DeezerClient::create();
 * $releases = $client->editorial()->metalReleases();
 *
 * // With caching
 * $cache = new FilesystemAdapter('deezer', 86400, '/tmp/cache');
 * $client = DeezerClient::create($cache);
 * ```
 */
class DeezerClient
{
    private const BASE_URL = 'https://api.deezer.com';

    private ?EditorialEndpoint $editorial = null;
    private ?AlbumEndpoint $albums = null;
    private ?ArtistEndpoint $artists = null;
    private ?PlaylistEndpoint $playlists = null;

    /** @var object|null Rate limiter instance (from four-rate-limiting) */
    private ?object $rateLimiter = null;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly ?CacheItemPoolInterface $cache = null,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Create a client with auto-discovered HTTP client and request factory
     *
     * @param CacheItemPoolInterface|null $cache PSR-6 cache for label caching
     * @param LoggerInterface|null $logger PSR-3 logger
     */
    public static function create(
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface $logger = null
    ): self {
        $httpClient = \Http\Discovery\Psr18ClientDiscovery::find();
        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();

        $client = new self(
            $httpClient,
            $requestFactory,
            $cache,
            $logger ?? new NullLogger()
        );

        // Try to set up rate limiting if four-rate-limiting is available
        $client->setupRateLimiting();

        return $client;
    }

    /**
     * Get the Editorial endpoint for genre-based releases
     */
    public function editorial(): EditorialEndpoint
    {
        return $this->editorial ??= new EditorialEndpoint($this);
    }

    /**
     * Get the Album endpoint
     */
    public function albums(): AlbumEndpoint
    {
        return $this->albums ??= new AlbumEndpoint($this, $this->cache);
    }

    /**
     * Get the Artist endpoint
     */
    public function artists(): ArtistEndpoint
    {
        return $this->artists ??= new ArtistEndpoint($this);
    }

    /**
     * Get the Playlist endpoint
     *
     * Useful for tracking label-curated "New Releases" playlists
     */
    public function playlists(): PlaylistEndpoint
    {
        return $this->playlists ??= new PlaylistEndpoint($this);
    }

    /**
     * Send a request to the Deezer API
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path API path (e.g., /album/123)
     * @param array<string, mixed> $query Query parameters
     * @return array<string, mixed> Decoded JSON response
     * @throws DeezerException On API errors
     *
     * @internal Used by endpoints, not meant for direct use
     */
    public function sendRequest(string $method, string $path, array $query = []): array
    {
        // Apply rate limiting if available
        $this->waitForRateLimit();

        // Build URL with query parameters
        $url = self::BASE_URL . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        // Create and send request
        $request = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'Four-Deezer-Client/1.0');

        $this->logger->debug('Deezer API request', [
            'method' => $method,
            'path' => $path,
            'query' => $query
        ]);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            $this->logger->error('Deezer API request failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw new DeezerException(
                'HTTP request failed: ' . $e->getMessage(),
                0,
                $path,
                $e
            );
        }

        return $this->handleResponse($response, $path);
    }

    /**
     * Handle API response and convert to array
     *
     * @return array<string, mixed>
     * @throws DeezerException
     */
    private function handleResponse(ResponseInterface $response, string $path): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Handle rate limiting (Deezer returns 4xx for rate limits)
        if ($statusCode === 429 || $statusCode === 403) {
            $this->logger->warning('Deezer API rate limited', ['path' => $path]);
            throw RateLimitException::exceeded(5000);
        }

        // Parse JSON
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException(
                'Failed to parse JSON response: ' . $e->getMessage(),
                $statusCode,
                $path,
                previous: $e
            );
        }

        // Check for Deezer API error in response body
        if (isset($data['error'])) {
            $this->logger->warning('Deezer API error', [
                'path' => $path,
                'error' => $data['error']
            ]);
            throw ApiException::fromResponse($data['error'], $path);
        }

        $this->logger->debug('Deezer API response', [
            'path' => $path,
            'status' => $statusCode,
            'data_count' => isset($data['data']) ? count($data['data']) : null
        ]);

        return $data;
    }

    /**
     * Set up rate limiting using four-rate-limiting if available
     */
    private function setupRateLimiting(): void
    {
        // Check if four-rate-limiting is available
        if (!class_exists(\Four\RateLimit\RateLimiterFactory::class)) {
            $this->logger->debug('four-rate-limiting not available, skipping rate limiting');
            return;
        }

        try {
            $factory = new \Four\RateLimit\RateLimiterFactory($this->logger);

            // Deezer limit: 50 requests per 5 seconds = 10 req/sec
            $this->rateLimiter = $factory->createCustom(
                algorithm: 'sliding_window',
                ratePerSecond: 10.0,
                burstCapacity: 50,
                safetyBuffer: 0.8,
                windowSizeMs: 5000
            );

            $this->logger->debug('Rate limiting enabled for Deezer API');
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to set up rate limiting', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Wait for rate limit if enabled
     */
    private function waitForRateLimit(): void
    {
        if ($this->rateLimiter === null) {
            return;
        }

        $key = 'deezer.api';

        // Use waitForAllowed if available (from four-rate-limiting)
        if (method_exists($this->rateLimiter, 'waitForAllowed')) {
            $allowed = $this->rateLimiter->waitForAllowed($key, 1, 30000);
            if (!$allowed) {
                throw RateLimitException::exceeded(30000);
            }
        } elseif (method_exists($this->rateLimiter, 'isAllowed')) {
            if (!$this->rateLimiter->isAllowed($key)) {
                $waitTime = method_exists($this->rateLimiter, 'getWaitTime')
                    ? $this->rateLimiter->getWaitTime($key)
                    : 1000;

                $this->logger->debug('Rate limited, waiting', ['ms' => $waitTime]);
                usleep($waitTime * 1000);
            }
        }
    }

    /**
     * Get the base URL for the Deezer API
     */
    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }
}
