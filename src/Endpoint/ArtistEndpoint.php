<?php

declare(strict_types=1);

namespace Four\Deezer\Endpoint;

use Four\Deezer\DeezerClient;
use Four\Deezer\DTO\Album;
use Four\Deezer\DTO\Artist;
use Four\Deezer\DTO\Release;
use Four\Deezer\DTO\Track;
use Four\Deezer\Exception\NotFoundException;

/**
 * Endpoint for Deezer Artist operations
 */
class ArtistEndpoint
{
    public function __construct(
        private readonly DeezerClient $client
    ) {
    }

    /**
     * Get artist details by ID
     *
     * @param int $artistId Deezer artist ID
     * @throws NotFoundException If artist doesn't exist
     */
    public function get(int $artistId): Artist
    {
        $data = $this->client->sendRequest('GET', "/artist/{$artistId}");

        if (isset($data['error'])) {
            throw NotFoundException::forArtist($artistId);
        }

        return Artist::fromArray($data);
    }

    /**
     * Get all albums by an artist
     *
     * @param int $artistId Deezer artist ID
     * @param int $limit Maximum number of albums
     * @return Release[] Array of releases (simplified album format)
     */
    public function getAlbums(int $artistId, int $limit = 100): array
    {
        $data = $this->client->sendRequest(
            'GET',
            "/artist/{$artistId}/albums",
            ['limit' => min($limit, 100)]
        );

        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        return array_map(
            fn(array $item) => Release::fromArray($item),
            $data['data']
        );
    }

    /**
     * Get top tracks by an artist
     *
     * @param int $artistId Deezer artist ID
     * @param int $limit Maximum number of tracks
     * @return Track[]
     */
    public function getTopTracks(int $artistId, int $limit = 50): array
    {
        $data = $this->client->sendRequest(
            'GET',
            "/artist/{$artistId}/top",
            ['limit' => min($limit, 100)]
        );

        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        return array_map(
            fn(array $item) => Track::fromArray($item),
            $data['data']
        );
    }

    /**
     * Get related artists
     *
     * @param int $artistId Deezer artist ID
     * @param int $limit Maximum number of artists
     * @return Artist[]
     */
    public function getRelated(int $artistId, int $limit = 20): array
    {
        $data = $this->client->sendRequest(
            'GET',
            "/artist/{$artistId}/related",
            ['limit' => min($limit, 100)]
        );

        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        return array_map(
            fn(array $item) => Artist::fromArray($item),
            $data['data']
        );
    }

    /**
     * Get new releases by an artist (albums released after a date)
     *
     * @param int $artistId Deezer artist ID
     * @param \DateTimeImmutable $since Only return releases on or after this date
     * @param int $limit Maximum number of albums to check
     * @return Release[]
     */
    public function getNewReleases(
        int $artistId,
        \DateTimeImmutable $since,
        int $limit = 50
    ): array {
        $albums = $this->getAlbums($artistId, $limit);

        return array_filter(
            $albums,
            fn(Release $r) => $r->isNewerThan($since)
        );
    }

    /**
     * Search for artists by name
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return Artist[]
     */
    public function search(string $query, int $limit = 20): array
    {
        $data = $this->client->sendRequest(
            'GET',
            '/search/artist',
            [
                'q' => $query,
                'limit' => min($limit, 100)
            ]
        );

        if (!isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        return array_map(
            fn(array $item) => Artist::fromArray($item),
            $data['data']
        );
    }
}
