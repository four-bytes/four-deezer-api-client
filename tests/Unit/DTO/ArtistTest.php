<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\DTO;

use Four\Deezer\DTO\Artist;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Artist::class)]
final class ArtistTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        $this->data = [
            'id' => 1026,
            'name' => 'Nightwish',
            'link' => 'https://www.deezer.com/artist/1026',
            'picture' => 'https://api.deezer.com/artist/1026/image',
            'picture_small' => 'https://cdn.deezer.com/images/artist/small.jpg',
            'picture_medium' => 'https://cdn.deezer.com/images/artist/medium.jpg',
            'picture_big' => 'https://cdn.deezer.com/images/artist/big.jpg',
            'picture_xl' => 'https://cdn.deezer.com/images/artist/xl.jpg',
            'nb_album' => 12,
            'nb_fan' => 1500000,
            'tracklist' => 'https://api.deezer.com/artist/1026/top?limit=50',
        ];
    }

    #[Test]
    public function fromArrayCreatesArtist(): void
    {
        $artist = Artist::fromArray($this->data);

        $this->assertInstanceOf(Artist::class, $artist);
        $this->assertSame(1026, $artist->id);
        $this->assertSame('Nightwish', $artist->name);
        $this->assertSame('https://www.deezer.com/artist/1026', $artist->link);
        $this->assertSame('https://cdn.deezer.com/images/artist/xl.jpg', $artist->pictureXl);
        $this->assertSame(12, $artist->nbAlbum);
        $this->assertSame(1500000, $artist->nbFan);
        $this->assertSame('https://api.deezer.com/artist/1026/top?limit=50', $artist->tracklist);
    }

    #[Test]
    public function fromArraySetsAllPictureVariants(): void
    {
        $artist = Artist::fromArray($this->data);

        $this->assertSame('https://api.deezer.com/artist/1026/image', $artist->picture);
        $this->assertSame('https://cdn.deezer.com/images/artist/small.jpg', $artist->pictureSmall);
        $this->assertSame('https://cdn.deezer.com/images/artist/medium.jpg', $artist->pictureMedium);
        $this->assertSame('https://cdn.deezer.com/images/artist/big.jpg', $artist->pictureBig);
    }

    #[Test]
    public function fromArrayWithMinimalDataUsesDefaults(): void
    {
        $artist = Artist::fromArray(['id' => 42, 'name' => 'Minimal Artist']);

        $this->assertSame(42, $artist->id);
        $this->assertSame('Minimal Artist', $artist->name);
        $this->assertNull($artist->link);
        $this->assertNull($artist->picture);
        $this->assertNull($artist->pictureSmall);
        $this->assertNull($artist->pictureMedium);
        $this->assertNull($artist->pictureBig);
        $this->assertNull($artist->pictureXl);
        $this->assertNull($artist->nbAlbum);
        $this->assertNull($artist->nbFan);
        $this->assertNull($artist->tracklist);
    }

    #[Test]
    public function fromArrayWithEmptyArrayUsesDefaults(): void
    {
        $artist = Artist::fromArray([]);

        $this->assertSame(0, $artist->id);
        $this->assertSame('Unknown Artist', $artist->name);
    }

    #[Test]
    public function toArrayReturnsCorrectFields(): void
    {
        $artist = Artist::fromArray($this->data);
        $result = $artist->toArray();

        $this->assertSame(1026, $result['id']);
        $this->assertSame('Nightwish', $result['name']);
        $this->assertSame('https://www.deezer.com/artist/1026', $result['link']);
        $this->assertSame('https://cdn.deezer.com/images/artist/xl.jpg', $result['picture_xl']);
    }

    #[Test]
    public function toArrayContainsExpectedKeys(): void
    {
        $artist = Artist::fromArray($this->data);
        $result = $artist->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('link', $result);
        $this->assertArrayHasKey('picture_xl', $result);
    }
}
