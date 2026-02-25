<?php

declare(strict_types=1);

namespace Four\Deezer\Endpoint;

use Four\Deezer\DeezerClient;
use Four\Deezer\DTO\Album;
use Four\Deezer\DTO\Track;
use Four\Deezer\Exception\NotFoundException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Endpoint for Deezer Album operations
 */
class AlbumEndpoint
{
    private const LABEL_CACHE_TTL = 604800; // 7 days
    private const LABEL_CACHE_PREFIX = 'deezer.album.label.';

    public function __construct(
        private readonly DeezerClient $client,
        private readonly ?CacheItemPoolInterface $cache = null
    ) {
    }

    /**
     * Get full album details by ID
     *
     * @param int $albumId Deezer album ID
     * @throws NotFoundException If album doesn't exist
     */
    public function get(int $albumId): Album
    {
        $data = $this->client->sendRequest('GET', "/album/{$albumId}");

        if (isset($data['error'])) {
            throw NotFoundException::forAlbum($albumId);
        }

        $album = Album::fromArray($data);

        // Cache the label for future lookups
        $this->cacheLabel($albumId, $album->label);

        return $album;
    }

    /**
     * Get only the label for an album (with caching)
     *
     * This is optimized for batch operations where you only need the label.
     * It checks cache first, only fetching from API if not cached.
     *
     * @param int $albumId Deezer album ID
     * @return string|null The label name, or null if not set
     */
    public function getLabel(int $albumId): ?string
    {
        // Check cache first
        if ($this->cache !== null) {
            $cacheItem = $this->cache->getItem(self::LABEL_CACHE_PREFIX . $albumId);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        }

        // Fetch from API
        $album = $this->get($albumId);
        return $album->label;
    }

    /**
     * Get tracks for an album
     *
     * @param int $albumId Deezer album ID
     * @param int $limit Maximum number of tracks
     * @return Track[]
     */
    public function getTracks(int $albumId, int $limit = 100): array
    {
        $data = $this->client->sendRequest(
            'GET',
            "/album/{$albumId}/tracks",
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
     * Check if an album is from a specific label
     *
     * @param int $albumId Deezer album ID
     * @param string $labelName Label name to check (case-insensitive partial match)
     */
    public function isFromLabel(int $albumId, string $labelName): bool
    {
        $label = $this->getLabel($albumId);

        if ($label === null) {
            return false;
        }

        return stripos($label, $labelName) !== false;
    }

    /**
     * Get labels for multiple albums (batch operation with caching)
     *
     * @param int[] $albumIds Array of album IDs
     * @return array<int, string|null> Map of album ID => label
     */
    public function getLabels(array $albumIds): array
    {
        $labels = [];
        $uncachedIds = [];

        // Check cache for each album
        foreach ($albumIds as $albumId) {
            if ($this->cache !== null) {
                $cacheItem = $this->cache->getItem(self::LABEL_CACHE_PREFIX . $albumId);
                if ($cacheItem->isHit()) {
                    $labels[$albumId] = $cacheItem->get();
                    continue;
                }
            }
            $uncachedIds[] = $albumId;
        }

        // Fetch uncached albums from API
        foreach ($uncachedIds as $albumId) {
            try {
                $labels[$albumId] = $this->getLabel($albumId);
            } catch (NotFoundException) {
                $labels[$albumId] = null;
            }
        }

        return $labels;
    }

    /**
     * Cache a label for an album
     */
    private function cacheLabel(int $albumId, ?string $label): void
    {
        if ($this->cache === null) {
            return;
        }

        $cacheItem = $this->cache->getItem(self::LABEL_CACHE_PREFIX . $albumId);
        $cacheItem->set($label);
        $cacheItem->expiresAfter(self::LABEL_CACHE_TTL);
        $this->cache->save($cacheItem);
    }
}
