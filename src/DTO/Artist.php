<?php

declare(strict_types=1);

namespace Four\Deezer\DTO;

/**
 * Represents a Deezer artist
 */
readonly class Artist
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $link = null,
        public ?string $picture = null,
        public ?string $pictureSmall = null,
        public ?string $pictureMedium = null,
        public ?string $pictureBig = null,
        public ?string $pictureXl = null,
        public ?int $nbAlbum = null,
        public ?int $nbFan = null,
        public ?string $tracklist = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? 'Unknown Artist',
            link: $data['link'] ?? null,
            picture: $data['picture'] ?? null,
            pictureSmall: $data['picture_small'] ?? null,
            pictureMedium: $data['picture_medium'] ?? null,
            pictureBig: $data['picture_big'] ?? null,
            pictureXl: $data['picture_xl'] ?? null,
            nbAlbum: $data['nb_album'] ?? null,
            nbFan: $data['nb_fan'] ?? null,
            tracklist: $data['tracklist'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'link' => $this->link,
            'picture_xl' => $this->pictureXl,
        ];
    }
}
