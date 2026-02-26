<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit;

use Four\Deezer\DeezerClient;
use Four\Deezer\Exception\ApiException;
use Four\Deezer\Exception\DeezerException;
use Four\Deezer\Exception\RateLimitException;
use Four\Http\Configuration\ClientConfig;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(DeezerClient::class)]
final class DeezerClientTest extends TestCase
{
    private Psr17Factory $psr17Factory;

    protected function setUp(): void
    {
        $this->psr17Factory = new Psr17Factory();
    }

    /**
     * Erstellt einen DeezerClient mit Mock-HTTP-Client.
     *
     * @param list<ResponseInterface> $responses
     */
    private function createClientWithMockResponses(array $responses): DeezerClient
    {
        $mockClient = new MockClient();
        foreach ($responses as $response) {
            $mockClient->addResponse($response);
        }

        $config = ClientConfig::create('https://api.deezer.com')
            ->withHeader('Accept', 'application/json')
            ->build();

        return new DeezerClient(
            $config,
            $mockClient,
            $this->psr17Factory,
            $this->psr17Factory,
        );
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
    public function sendRequestWith404ThrowsDeezerException(): void
    {
        $response = new Response(404, [], 'Not Found');

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(DeezerException::class);
        $client->sendRequest('GET', '/album/999999');
    }

    #[Test]
    public function sendRequestWith500ThrowsDeezerException(): void
    {
        $response = new Response(500, [], 'Internal Server Error');

        $client = $this->createClientWithMockResponses([$response]);

        $this->expectException(DeezerException::class);
        $client->sendRequest('GET', '/album/123');
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
