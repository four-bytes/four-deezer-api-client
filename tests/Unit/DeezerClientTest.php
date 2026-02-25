<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit;

use Four\Deezer\DeezerClient;
use Four\Deezer\Exception\ApiException;
use Four\Deezer\Exception\DeezerException;
use Four\Deezer\Exception\RateLimitException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeezerClient::class)]
final class DeezerClientTest extends TestCase
{
    private HttpFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new HttpFactory();
    }

    /**
     * Erstellt einen DeezerClient mit Guzzle MockHandler.
     *
     * @param Response[] $responses
     */
    private function createClientWithMockResponses(array $responses): DeezerClient
    {
        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        return new DeezerClient($guzzleClient, $this->factory);
    }

    // ---------------------------------------------------------------
    // getBaseUrl
    // ---------------------------------------------------------------

    #[Test]
    public function getBaseUrlReturnsApiDeezerCom(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $this->assertSame('https://api.deezer.com', $client->getBaseUrl());
    }

    // ---------------------------------------------------------------
    // sendRequest – URL-Aufbau
    // ---------------------------------------------------------------

    #[Test]
    public function sendRequestWithValidJsonResponseReturnsArray(): void
    {
        $body = json_encode(['id' => 123, 'title' => 'Test Album'], JSON_THROW_ON_ERROR);
        $response = new Response(200, ['Content-Type' => 'application/json'], $body);

        $client = $this->createClientWithMockResponses([$response]);
        $result = $client->sendRequest('GET', '/album/123');

        $this->assertSame(123, $result['id']);
        $this->assertSame('Test Album', $result['title']);
    }

    #[Test]
    public function sendRequestWithQueryParametersReturnsCorrectData(): void
    {
        $body = json_encode(['data' => [], 'total' => 0], JSON_THROW_ON_ERROR);
        $response = new Response(200, [], $body);

        $client = $this->createClientWithMockResponses([$response]);
        $result = $client->sendRequest('GET', '/search', ['q' => 'Nightwish', 'limit' => 25]);

        $this->assertSame([], $result['data']);
        $this->assertSame(0, $result['total']);
    }

    #[Test]
    public function sendRequestWithEmptyQueryParametersWorks(): void
    {
        $body = json_encode(['id' => 1], JSON_THROW_ON_ERROR);
        $response = new Response(200, [], $body);

        $client = $this->createClientWithMockResponses([$response]);
        $result = $client->sendRequest('GET', '/album/1', []);

        $this->assertSame(1, $result['id']);
    }

    // ---------------------------------------------------------------
    // handleResponse – Statuscodes (via sendRequest)
    // ---------------------------------------------------------------

    #[Test]
    public function sendRequestWith429ThrowsRateLimitException(): void
    {
        $response = new Response(429, [], 'Too Many Requests');

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(RateLimitException::class);
        $client->sendRequest('GET', '/album/123');
    }

    #[Test]
    public function sendRequestWith403ThrowsRateLimitException(): void
    {
        $response = new Response(403, [], 'Forbidden');

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(RateLimitException::class);
        $client->sendRequest('GET', '/album/123');
    }

    #[Test]
    public function sendRequestWithRateLimitResponseHasCorrectRetryAfter(): void
    {
        $response = new Response(429, [], 'Too Many Requests');

        $client = $this->createClientWithMockResponses([$response]);

        try {
            $client->sendRequest('GET', '/album/123');
            $this->fail('RateLimitException wurde nicht geworfen');
        } catch (RateLimitException $e) {
            $this->assertSame(5000, $e->getRetryAfterMs());
        }
    }

    #[Test]
    public function sendRequestWithDeezerErrorInBodyThrowsApiException(): void
    {
        $body = json_encode([
            'error' => [
                'code' => 800,
                'message' => 'Token invalid',
                'type' => 'OAuthException',
            ],
        ], JSON_THROW_ON_ERROR);
        $response = new Response(200, [], $body);

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(ApiException::class);
        $client->sendRequest('GET', '/album/123');
    }

    #[Test]
    public function sendRequestWithDeezerErrorContainsCorrectErrorCode(): void
    {
        $body = json_encode([
            'error' => [
                'code' => 800,
                'message' => 'Token invalid',
            ],
        ], JSON_THROW_ON_ERROR);
        $response = new Response(200, [], $body);

        $client = $this->createClientWithMockResponses([$response]);

        try {
            $client->sendRequest('GET', '/album/123');
            $this->fail('ApiException wurde nicht geworfen');
        } catch (ApiException $e) {
            $this->assertSame(800, $e->getErrorCode());
            $this->assertSame('Token invalid', $e->getMessage());
        }
    }

    #[Test]
    public function sendRequestWithInvalidJsonThrowsApiException(): void
    {
        $response = new Response(200, [], 'das ist kein JSON {{{');

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(ApiException::class);
        $client->sendRequest('GET', '/album/123');
    }

    #[Test]
    public function sendRequestWithHttpClientExceptionThrowsDeezerException(): void
    {
        // Guzzle MockHandler mit Exception statt Response
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('GET', 'https://api.deezer.com/album/1')
            ),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
        $client = new DeezerClient($guzzleClient, $this->factory);

        $this->expectException(DeezerException::class);
        $client->sendRequest('GET', '/album/1');
    }

    // ---------------------------------------------------------------
    // Endpoint-Factories
    // ---------------------------------------------------------------

    #[Test]
    public function editorialReturnsEditorialEndpoint(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $editorial = $client->editorial();

        $this->assertInstanceOf(\Four\Deezer\Endpoint\EditorialEndpoint::class, $editorial);
    }

    #[Test]
    public function editorialReturnsSameInstanceOnMultipleCalls(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $first = $client->editorial();
        $second = $client->editorial();

        $this->assertSame($first, $second);
    }

    #[Test]
    public function albumsReturnsAlbumEndpoint(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $albums = $client->albums();

        $this->assertInstanceOf(\Four\Deezer\Endpoint\AlbumEndpoint::class, $albums);
    }

    #[Test]
    public function artistsReturnsArtistEndpoint(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $artists = $client->artists();

        $this->assertInstanceOf(\Four\Deezer\Endpoint\ArtistEndpoint::class, $artists);
    }

    #[Test]
    public function playlistsReturnsPlaylistEndpoint(): void
    {
        $client = $this->createClientWithMockResponses([]);

        $playlists = $client->playlists();

        $this->assertInstanceOf(\Four\Deezer\Endpoint\PlaylistEndpoint::class, $playlists);
    }
}
