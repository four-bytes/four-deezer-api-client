<?php

declare(strict_types=1);

namespace Four\Deezer\DTO;

/**
 * Represents a simplified album from editorial/releases endpoint
 *
 * This is a lighter version of Album, used in lists where we don't need all details.
 * To get full album details (including label), use AlbumEndpoint::get()
 */
readonly class Release
{
    public function __construct(
        public int $id,
        public string $title,
        public Artist $artist,
        public \DateTimeImmutable $releaseDate,
        public ?string $recordType = null,
        public ?string $cover = null,
        public ?string $coverSmall = null,
        public ?string $coverMedium = null,
        public ?string $coverBig = null,
        public ?string $coverXl = null,
        public ?string $tracklist = null,
        public bool $explicitLyrics = false,
        public ?int $genreId = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            artist: Artist::fromArray($data['artist'] ?? []),
            releaseDate: new \DateTimeImmutable($data['release_date']),
            recordType: $data['record_type'] ?? null,
            cover: $data['cover'] ?? null,
            coverSmall: $data['cover_small'] ?? null,
            coverMedium: $data['cover_medium'] ?? null,
            coverBig: $data['cover_big'] ?? null,
            coverXl: $data['cover_xl'] ?? null,
            tracklist: $data['tracklist'] ?? null,
            explicitLyrics: $data['explicit_lyrics'] ?? false,
            genreId: $data['genre_id'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist->toArray(),
            'release_date' => $this->releaseDate->format('Y-m-d'),
            'record_type' => $this->recordType,
            'cover_xl' => $this->coverXl,
        ];
    }

    /**
     * Check if this release is newer than a given date
     */
    public function isNewerThan(\DateTimeImmutable $date): bool
    {
        return $this->releaseDate >= $date;
    }

    /**
     * Check if this is a single
     */
    public function isSingle(): bool
    {
        return $this->recordType === 'single';
    }

    /**
     * Check if this is an album
     */
    public function isAlbum(): bool
    {
        return $this->recordType === 'album';
    }

    /**
     * Check if this is an EP
     */
    public function isEP(): bool
    {
        return $this->recordType === 'ep';
    }
}
