<?php

declare(strict_types=1);

namespace Four\Deezer\Endpoint;

use Four\Deezer\DeezerClient;
use Four\Deezer\DTO\Artist;
use Four\Deezer\DTO\Track;
use Four\Deezer\Exception\NotFoundException;

/**
 * Endpoint for Deezer Playlist operations
 *
 * Perfect for tracking label-curated "New Releases" playlists
 */
class PlaylistEndpoint
{
    // Known label playlist IDs
    public const NUCLEAR_BLAST_NEW_RELEASES = 1059795261;
    public const CENTURY_MEDIA_NEW_RELEASES = 1313621735; // TODO: verify
    public const NAPALM_RECORDS_NEW_RELEASES = 4403076324; // TODO: verify

    public function __construct(
        private readonly DeezerClient $client
    ) {
    }

    /**
     * Get playlist metadata
     *
     * @param int $playlistId Deezer playlist ID
     * @return array<string, mixed> Playlist info (id, title, description, creator, etc.)
     */
    public function get(int $playlistId): array
    {
        $data = $this->client->sendRequest('GET', "/playlist/{$playlistId}");

        if (isset($data['error'])) {
            throw new NotFoundException(
                "Playlist with ID {$playlistId} not found",
                404,
                "/playlist/{$playlistId}"
            );
        }

        return $data;
    }

    /**
     * Get all tracks from a playlist
     *
     * Returns tracks with full metadata including:
     * - time_add: Unix timestamp when added to playlist
     * - album.upc: UPC code for the album
     * - isrc: ISRC code for the track
     *
     * @param int $playlistId Deezer playlist ID
     * @param int $limit Maximum tracks to return (max 100 per request)
     * @return array<int, array<string, mixed>> Raw track data with album info
     */
    public function getTracks(int $playlistId, int $limit = 100): array
    {
        $data = $this->client->sendRequest(
            'GET',
            "/playlist/{$playlistId}/tracks",
            ['limit' => min($limit, 100)]
        );

        return $data['data'] ?? [];
    }

    /**
     * Get tracks added since a specific date
     *
     * @param int $playlistId Deezer playlist ID
     * @param \DateTimeImmutable $since Only return tracks added after this date
     * @return array<int, array<string, mixed>> Filtered track data
     */
    public function getTracksSince(int $playlistId, \DateTimeImmutable $since): array
    {
        $tracks = $this->getTracks($playlistId, 100);
        $sinceTimestamp = $since->getTimestamp();

        return array_filter($tracks, function (array $track) use ($sinceTimestamp) {
            return isset($track['time_add']) && $track['time_add'] >= $sinceTimestamp;
        });
    }

    /**
     * Get unique albums from a playlist
     *
     * Groups tracks by album and returns album info with UPC
     *
     * @param int $playlistId Deezer playlist ID
     * @return array<int, array<string, mixed>> Map of album_id => album info
     */
    public function getAlbums(int $playlistId): array
    {
        $tracks = $this->getTracks($playlistId, 100);
        $albums = [];

        foreach ($tracks as $track) {
            $albumId = $track['album']['id'] ?? null;
            if ($albumId && !isset($albums[$albumId])) {
                $albums[$albumId] = [
                    'id' => $albumId,
                    'title' => $track['album']['title'] ?? 'Unknown',
                    'upc' => $track['album']['upc'] ?? null,
                    'artist' => $track['artist']['name'] ?? 'Unknown',
                    'artist_id' => $track['artist']['id'] ?? null,
                    'cover_xl' => $track['album']['cover_xl'] ?? null,
                    'tracks_in_playlist' => [],
                ];
            }
            if ($albumId) {
                $albums[$albumId]['tracks_in_playlist'][] = $track['title'];
            }
        }

        return $albums;
    }

    /**
     * Get new releases from Nuclear Blast's official playlist
     *
     * @param \DateTimeImmutable|null $since Only tracks added after this date
     * @return array<int, array<string, mixed>> Track data
     */
    public function getNuclearBlastReleases(?\DateTimeImmutable $since = null): array
    {
        if ($since !== null) {
            return $this->getTracksSince(self::NUCLEAR_BLAST_NEW_RELEASES, $since);
        }
        return $this->getTracks(self::NUCLEAR_BLAST_NEW_RELEASES);
    }

    /**
     * Parse playlist tracks into a structured format for SCR
     *
     * @param int $playlistId Deezer playlist ID
     * @return array<int, array<string, mixed>> Structured release data ready for shop import
     */
    public function parseForShopImport(int $playlistId): array
    {
        $tracks = $this->getTracks($playlistId, 100);
        $releases = [];

        foreach ($tracks as $track) {
            $albumId = $track['album']['id'] ?? null;
            if (!$albumId) {
                continue;
            }

            // Group by album
            if (!isset($releases[$albumId])) {
                $releases[$albumId] = [
                    'album_id' => $albumId,
                    'album_title' => $track['album']['title'] ?? 'Unknown',
                    'upc' => $track['album']['upc'] ?? null,
                    'artist' => $track['artist']['name'] ?? 'Unknown',
                    'artist_id' => $track['artist']['id'] ?? null,
                    'cover_url' => $track['album']['cover_xl'] ?? $track['album']['cover_big'] ?? null,
                    'deezer_url' => "https://www.deezer.com/album/{$albumId}",
                    'added_to_playlist' => isset($track['time_add'])
                        ? date('Y-m-d H:i:s', $track['time_add'])
                        : null,
                    'singles' => [],
                ];
            }

            // Add track as single
            $releases[$albumId]['singles'][] = [
                'track_id' => $track['id'],
                'title' => $track['title'],
                'isrc' => $track['isrc'] ?? null,
                'duration' => $track['duration'] ?? 0,
                'explicit' => $track['explicit_lyrics'] ?? false,
            ];
        }

        return array_values($releases);
    }
}
