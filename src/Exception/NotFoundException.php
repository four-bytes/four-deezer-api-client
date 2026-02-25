<?php

declare(strict_types=1);

namespace Four\Deezer\Exception;

/**
 * Exception thrown when a resource is not found (404)
 */
class NotFoundException extends DeezerException
{
    public static function forAlbum(int $albumId): self
    {
        return new self(
            message: "Album with ID {$albumId} not found",
            statusCode: 404,
            endpoint: "/album/{$albumId}"
        );
    }

    public static function forArtist(int $artistId): self
    {
        return new self(
            message: "Artist with ID {$artistId} not found",
            statusCode: 404,
            endpoint: "/artist/{$artistId}"
        );
    }

    public static function forTrack(int $trackId): self
    {
        return new self(
            message: "Track with ID {$trackId} not found",
            statusCode: 404,
            endpoint: "/track/{$trackId}"
        );
    }
}
