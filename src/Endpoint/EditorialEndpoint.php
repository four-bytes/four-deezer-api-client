<?php

declare(strict_types=1);

namespace Four\Deezer\Endpoint;

use Four\Deezer\DeezerClient;
use Four\Deezer\DTO\Release;

/**
 * Endpoint for Deezer Editorial content (curated releases by genre)
 */
class EditorialEndpoint
{
    // Genre IDs for common genres
    public const GENRE_ALL = 0;
    public const GENRE_POP = 132;
    public const GENRE_RAP_HIPHOP = 116;
    public const GENRE_ROCK = 152;
    public const GENRE_DANCE = 113;
    public const GENRE_RNB = 165;
    public const GENRE_ALTERNATIVE = 85;
    public const GENRE_ELECTRO = 106;
    public const GENRE_FOLK = 466;
    public const GENRE_REGGAE = 144;
    public const GENRE_JAZZ = 129;
    public const GENRE_CLASSICAL = 98;
    public const GENRE_METAL = 464;
    public const GENRE_SOUL_FUNK = 169;
    public const GENRE_BLUES = 153;
    public const GENRE_COUNTRY = 84;
    public const GENRE_LATIN = 197;

    public function __construct(
        private readonly DeezerClient $client
    ) {
    }

    /**
     * Get new releases for a genre
     *
     * @param int $genreId Genre ID (use class constants, default: METAL)
     * @param int $limit Maximum number of releases to return (max 100)
     * @return Release[]
     */
    public function releases(int $genreId = self::GENRE_METAL, int $limit = 100): array
    {
        $limit = min($limit, 100); // Deezer max is 100

        $data = $this->client->sendRequest(
            'GET',
            "/editorial/{$genreId}/releases",
            ['limit' => $limit]
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
     * Get new releases for Metal genre
     *
     * @param int $limit Maximum number of releases
     * @return Release[]
     */
    public function metalReleases(int $limit = 100): array
    {
        return $this->releases(self::GENRE_METAL, $limit);
    }

    /**
     * Get new releases for Rock genre
     *
     * @param int $limit Maximum number of releases
     * @return Release[]
     */
    public function rockReleases(int $limit = 100): array
    {
        return $this->releases(self::GENRE_ROCK, $limit);
    }

    /**
     * Get new releases filtered by date
     *
     * @param int $genreId Genre ID
     * @param \DateTimeImmutable $since Only return releases on or after this date
     * @param int $limit Maximum number of releases to fetch
     * @return Release[]
     */
    public function releasesSince(
        int $genreId,
        \DateTimeImmutable $since,
        int $limit = 100
    ): array {
        $releases = $this->releases($genreId, $limit);

        return array_filter(
            $releases,
            fn(Release $r) => $r->isNewerThan($since)
        );
    }

    /**
     * Get available editorial genres
     *
     * @return array<int, string> Array of genre ID => name
     */
    public function getGenres(): array
    {
        $data = $this->client->sendRequest('GET', '/editorial');

        $genres = [];
        foreach ($data['data'] ?? [] as $genre) {
            $genres[$genre['id']] = $genre['name'];
        }

        return $genres;
    }
}
