<?php

declare(strict_types=1);

namespace Four\Deezer\DTO;

/**
 * Represents a Deezer track
 */
readonly class Track
{
    public function __construct(
        public int $id,
        public string $title,
        public string $titleShort,
        public ?string $isrc,
        public string $link,
        public int $duration,
        public int $trackPosition,
        public int $diskNumber,
        public int $rank,
        public bool $explicitLyrics,
        public ?string $preview,
        public Artist $artist,
        public ?int $albumId = null,
        public ?\DateTimeImmutable $releaseDate = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $releaseDate = null;
        if (isset($data['release_date']) && $data['release_date']) {
            $releaseDate = new \DateTimeImmutable($data['release_date']);
        }

        return new self(
            id: $data['id'],
            title: $data['title'],
            titleShort: $data['title_short'] ?? $data['title'],
            isrc: $data['isrc'] ?? null,
            link: $data['link'],
            duration: $data['duration'] ?? 0,
            trackPosition: $data['track_position'] ?? 1,
            diskNumber: $data['disk_number'] ?? 1,
            rank: $data['rank'] ?? 0,
            explicitLyrics: $data['explicit_lyrics'] ?? false,
            preview: $data['preview'] ?? null,
            artist: Artist::fromArray($data['artist'] ?? []),
            albumId: $data['album']['id'] ?? null,
            releaseDate: $releaseDate
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
            'isrc' => $this->isrc,
            'duration' => $this->duration,
            'artist' => $this->artist->toArray(),
            'link' => $this->link,
        ];
    }

    /**
     * Get duration formatted as MM:SS
     */
    public function getDurationFormatted(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
