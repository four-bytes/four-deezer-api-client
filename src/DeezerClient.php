<?php

declare(strict_types=1);

namespace Four\Deezer;

use Four\Deezer\Endpoint\AlbumEndpoint;
use Four\Deezer\Endpoint\ArtistEndpoint;
use Four\Deezer\Endpoint\EditorialEndpoint;
use Four\Deezer\Endpoint\PlaylistEndpoint;
use Four\Deezer\Exception\DeezerException;
use Four\Http\Client\ApiClient;
use Four\Http\Configuration\ClientConfig;
use Four\Http\Factory\HttpClientFactory;
use Four\RateLimit\RateLimitConfiguration;
use Four\RateLimit\RateLimiterFactory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Deezer API Client
 *
 * Erbt von ApiClient (four-http-client) für PSR-18, Middleware-Stack,
 * Error-Mapping und Retry-Logik. Kein eigener HTTP-Transport nötig.
 *
 * @example
 * ```php
 * $client = DeezerClient::create();
 * $releases = $client->editorial()->metalReleases();
 *
 * $cache = new FilesystemAdapter('deezer', 86400, '/tmp/cache');
 * $client = DeezerClient::create($cache);
 * ```
 */
class DeezerClient extends ApiClient
{
    private const BASE_URL = 'https://api.deezer.com';

    private ?EditorialEndpoint $editorial = null;
    private ?AlbumEndpoint $albums = null;
    private ?ArtistEndpoint $artists = null;
    private ?PlaylistEndpoint $playlists = null;

    public function __construct(
        ClientConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        private readonly ?CacheItemPoolInterface $cache = null,
    ) {
        parent::__construct($config, $httpClient, $requestFactory, $streamFactory);
    }

    /**
     * Erstellt einen Client mit auto-discoveredm HTTP-Transport und optionalem Cache.
     */
    public static function create(
        ?CacheItemPoolInterface $cache = null,
        ?LoggerInterface $logger = null,
    ): self {
        $logger ??= new NullLogger();

        // Rate Limiter: Deezer = 50 req/5s Sliding Window
        $rateLimiter = (new RateLimiterFactory())->create(
            new RateLimitConfiguration(
                algorithm: RateLimitConfiguration::ALGORITHM_SLIDING_WINDOW,
                ratePerSecond: 10.0,
                burstCapacity: 50,
                safetyBuffer: 0.8,
                windowSizeMs: 5000,
            )
        );

        $config = ClientConfig::create(self::BASE_URL)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'Four-Deezer-Client/1.0')
            ->withTimeout(15.0)
            ->withLogging($logger)
            ->withRateLimit($rateLimiter)
            ->withRetries()
            ->build();

        $factory = new HttpClientFactory();
        $httpClient = $factory->create($config);

        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory  = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();

        return new self($config, $httpClient, $requestFactory, $streamFactory, $cache);
    }

    public function editorial(): EditorialEndpoint
    {
        return $this->editorial ??= new EditorialEndpoint($this);
    }

    public function albums(): AlbumEndpoint
    {
        return $this->albums ??= new AlbumEndpoint($this, $this->cache);
    }

    public function artists(): ArtistEndpoint
    {
        return $this->artists ??= new ArtistEndpoint($this);
    }

    public function playlists(): PlaylistEndpoint
    {
        return $this->playlists ??= new PlaylistEndpoint($this);
    }

    /**
     * Sendet einen Request an die Deezer API.
     * Wird intern von Endpoints verwendet.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     * @throws DeezerException
     * @internal
     */
    public function sendRequest(string $method, string $path, array $query = []): array
    {
        try {
            return match (strtoupper($method)) {
                'GET'  => $this->httpGet(self::BASE_URL . $path, $query),
                'POST' => $this->httpPost(self::BASE_URL . $path, $query),
                default => $this->httpGet(self::BASE_URL . $path, $query),
            };
        } catch (\Four\Http\Exception\RateLimitException $e) {
            throw new \Four\Deezer\Exception\RateLimitException(
                message: 'Deezer rate limit exceeded',
                retryAfterMs: 5000,
                endpoint: $path,
            );
        } catch (\Four\Http\Exception\NotFoundException $e) {
            throw new \Four\Deezer\Exception\NotFoundException(
                message: "Deezer resource not found: {$path}",
                statusCode: 404,
                endpoint: $path,
                previous: $e,
            );
        } catch (\Four\Http\Exception\HttpClientException $e) {
            throw new DeezerException(
                message: 'Deezer API error: ' . $e->getMessage(),
                statusCode: $e->getCode(),
                endpoint: $path,
                previous: $e,
            );
        }
    }

    public function getBaseUrl(): string
    {
        return self::BASE_URL;
    }
}
