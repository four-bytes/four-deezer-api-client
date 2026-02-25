<?php

declare(strict_types=1);

namespace Four\Deezer\DTO;

/**
 * Represents a full Deezer album with all details
 */
readonly class Album
{
    public function __construct(
        public int $id,
        public string $title,
        public Artist $artist,
        public ?string $label,
        public \DateTimeImmutable $releaseDate,
        public string $link,
        public ?string $upc = null,
        public int $nbTracks = 0,
        public int $duration = 0,
        public int $fans = 0,
        public bool $explicitLyrics = false,
        public ?string $recordType = null,
        public ?string $cover = null,
        public ?string $coverSmall = null,
        public ?string $coverMedium = null,
        public ?string $coverBig = null,
        public ?string $coverXl = null,
        /** @var Genre[] */
        public array $genres = [],
        /** @var Track[] */
        public array $tracks = [],
        /** @var Artist[] */
        public array $contributors = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $genres = [];
        if (isset($data['genres']['data'])) {
            $genres = array_map(
                fn(array $g) => Genre::fromArray($g),
                $data['genres']['data']
            );
        }

        $tracks = [];
        if (isset($data['tracks']['data'])) {
            $tracks = array_map(
                fn(array $t) => Track::fromArray($t),
                $data['tracks']['data']
            );
        }

        $contributors = [];
        if (isset($data['contributors'])) {
            $contributors = array_map(
                fn(array $c) => Artist::fromArray($c),
                $data['contributors']
            );
        }

        // Artist kann direkt oder als erster Contributor kommen
        $artistData = $data['artist'] ?? $data['contributors'][0] ?? ['id' => 0, 'name' => 'Unknown'];

        return new self(
            id: $data['id'],
            title: $data['title'],
            artist: Artist::fromArray($artistData),
            label: $data['label'] ?? null,
            releaseDate: new \DateTimeImmutable($data['release_date']),
            link: $data['link'],
            upc: $data['upc'] ?? null,
            nbTracks: $data['nb_tracks'] ?? 0,
            duration: $data['duration'] ?? 0,
            fans: $data['fans'] ?? 0,
            explicitLyrics: $data['explicit_lyrics'] ?? false,
            recordType: $data['record_type'] ?? null,
            cover: $data['cover'] ?? null,
            coverSmall: $data['cover_small'] ?? null,
            coverMedium: $data['cover_medium'] ?? null,
            coverBig: $data['cover_big'] ?? null,
            coverXl: $data['cover_xl'] ?? null,
            genres: $genres,
            tracks: $tracks,
            contributors: $contributors
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
            'label' => $this->label,
            'release_date' => $this->releaseDate->format('Y-m-d'),
            'link' => $this->link,
            'upc' => $this->upc,
            'nb_tracks' => $this->nbTracks,
            'record_type' => $this->recordType,
            'cover_xl' => $this->coverXl,
            'genres' => array_map(fn(Genre $g) => $g->toArray(), $this->genres),
        ];
    }

    /**
     * Check if this album is from a specific label
     */
    public function isFromLabel(string $labelName): bool
    {
        if ($this->label === null) {
            return false;
        }
        return stripos($this->label, $labelName) !== false;
    }

    /**
     * Get duration formatted as HH:MM:SS or MM:SS
     */
    public function getDurationFormatted(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
