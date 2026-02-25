<?php

declare(strict_types=1);

namespace Four\Deezer\DTO;

/**
 * Represents a Deezer genre
 */
readonly class Genre
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $picture = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? 'Unknown',
            picture: $data['picture'] ?? null
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
        ];
    }
}
