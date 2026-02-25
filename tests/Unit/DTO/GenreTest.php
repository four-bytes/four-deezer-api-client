<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\DTO;

use Four\Deezer\DTO\Genre;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Genre::class)]
final class GenreTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesGenre(): void
    {
        $data = [
            'id' => 464,
            'name' => 'Metal',
            'picture' => 'https://api.deezer.com/genre/464/image',
        ];

        $genre = Genre::fromArray($data);

        $this->assertInstanceOf(Genre::class, $genre);
        $this->assertSame(464, $genre->id);
        $this->assertSame('Metal', $genre->name);
        $this->assertSame('https://api.deezer.com/genre/464/image', $genre->picture);
    }

    #[Test]
    public function fromArrayWithNullPicture(): void
    {
        $data = [
            'id' => 464,
            'name' => 'Metal',
            'picture' => null,
        ];

        $genre = Genre::fromArray($data);

        $this->assertNull($genre->picture);
    }

    #[Test]
    public function fromArrayWithoutPicture(): void
    {
        $data = [
            'id' => 132,
            'name' => 'Pop',
        ];

        $genre = Genre::fromArray($data);

        $this->assertSame(132, $genre->id);
        $this->assertSame('Pop', $genre->name);
        $this->assertNull($genre->picture);
    }

    #[Test]
    public function fromArrayWithEmptyArrayUsesDefaults(): void
    {
        $genre = Genre::fromArray([]);

        $this->assertSame(0, $genre->id);
        $this->assertSame('Unknown', $genre->name);
        $this->assertNull($genre->picture);
    }

    #[Test]
    public function toArrayReturnsCorrectFields(): void
    {
        $genre = Genre::fromArray(['id' => 464, 'name' => 'Metal']);
        $result = $genre->toArray();

        $this->assertSame(464, $result['id']);
        $this->assertSame('Metal', $result['name']);
    }

    #[Test]
    public function toArrayContainsOnlyIdAndName(): void
    {
        $genre = Genre::fromArray([
            'id' => 464,
            'name' => 'Metal',
            'picture' => 'https://example.com/pic.jpg',
        ]);
        $result = $genre->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        // picture wird in toArray() nicht exportiert
        $this->assertArrayNotHasKey('picture', $result);
    }
}
