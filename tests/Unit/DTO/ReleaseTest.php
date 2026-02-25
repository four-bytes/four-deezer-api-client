<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\DTO;

use Four\Deezer\DTO\Artist;
use Four\Deezer\DTO\Release;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Release::class)]
final class ReleaseTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        $this->data = [
            'id' => 456,
            'title' => 'Heresy and Cruelty',
            'artist' => [
                'id' => 7890,
                'name' => 'Abbath',
            ],
            'release_date' => '2024-01-19',
            'record_type' => 'album',
            'cover' => 'https://cdn.deezer.com/cover.jpg',
            'cover_small' => 'https://cdn.deezer.com/cover_small.jpg',
            'cover_medium' => 'https://cdn.deezer.com/cover_medium.jpg',
            'cover_big' => 'https://cdn.deezer.com/cover_big.jpg',
            'cover_xl' => 'https://cdn.deezer.com/cover_xl.jpg',
            'tracklist' => 'https://api.deezer.com/album/456/tracks',
            'explicit_lyrics' => false,
            'genre_id' => 464,
        ];
    }

    #[Test]
    public function fromArrayCreatesRelease(): void
    {
        $release = Release::fromArray($this->data);

        $this->assertInstanceOf(Release::class, $release);
        $this->assertSame(456, $release->id);
        $this->assertSame('Heresy and Cruelty', $release->title);
        $this->assertSame('album', $release->recordType);
        $this->assertSame('https://cdn.deezer.com/cover_xl.jpg', $release->coverXl);
        $this->assertSame('https://api.deezer.com/album/456/tracks', $release->tracklist);
        $this->assertFalse($release->explicitLyrics);
        $this->assertSame(464, $release->genreId);
    }

    #[Test]
    public function fromArrayCreatesCorrectArtist(): void
    {
        $release = Release::fromArray($this->data);

        $this->assertInstanceOf(Artist::class, $release->artist);
        $this->assertSame(7890, $release->artist->id);
        $this->assertSame('Abbath', $release->artist->name);
    }

    #[Test]
    public function fromArrayReleaseDateIsDateTimeImmutable(): void
    {
        $release = Release::fromArray($this->data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $release->releaseDate);
        $this->assertSame('2024-01-19', $release->releaseDate->format('Y-m-d'));
    }

    #[Test]
    public function fromArraySetsAllCoverVariants(): void
    {
        $release = Release::fromArray($this->data);

        $this->assertSame('https://cdn.deezer.com/cover.jpg', $release->cover);
        $this->assertSame('https://cdn.deezer.com/cover_small.jpg', $release->coverSmall);
        $this->assertSame('https://cdn.deezer.com/cover_medium.jpg', $release->coverMedium);
        $this->assertSame('https://cdn.deezer.com/cover_big.jpg', $release->coverBig);
    }

    #[Test]
    public function toArrayReturnsCorrectFields(): void
    {
        $release = Release::fromArray($this->data);
        $result = $release->toArray();

        $this->assertSame(456, $result['id']);
        $this->assertSame('Heresy and Cruelty', $result['title']);
        $this->assertSame('2024-01-19', $result['release_date']);
        $this->assertSame('album', $result['record_type']);
        $this->assertSame('https://cdn.deezer.com/cover_xl.jpg', $result['cover_xl']);
        $this->assertIsArray($result['artist']);
    }

    #[Test]
    public function isNewerThanReturnsTrueForOlderDate(): void
    {
        $release = Release::fromArray($this->data);
        $olderDate = new \DateTimeImmutable('2023-01-01');

        $this->assertTrue($release->isNewerThan($olderDate));
    }

    #[Test]
    public function isNewerThanReturnsFalseForNewerDate(): void
    {
        $release = Release::fromArray($this->data);
        $newerDate = new \DateTimeImmutable('2025-06-01');

        $this->assertFalse($release->isNewerThan($newerDate));
    }

    #[Test]
    public function isNewerThanReturnsTrueForSameDate(): void
    {
        $release = Release::fromArray($this->data);
        $sameDate = new \DateTimeImmutable('2024-01-19');

        $this->assertTrue($release->isNewerThan($sameDate));
    }

    #[Test]
    public function isAlbumReturnsTrueForAlbumType(): void
    {
        $release = Release::fromArray($this->data);

        $this->assertTrue($release->isAlbum());
        $this->assertFalse($release->isSingle());
        $this->assertFalse($release->isEP());
    }

    #[Test]
    public function isSingleReturnsTrueForSingleType(): void
    {
        $data = $this->data;
        $data['record_type'] = 'single';
        $release = Release::fromArray($data);

        $this->assertTrue($release->isSingle());
        $this->assertFalse($release->isAlbum());
        $this->assertFalse($release->isEP());
    }

    #[Test]
    public function isEpReturnsTrueForEpType(): void
    {
        $data = $this->data;
        $data['record_type'] = 'ep';
        $release = Release::fromArray($data);

        $this->assertTrue($release->isEP());
        $this->assertFalse($release->isAlbum());
        $this->assertFalse($release->isSingle());
    }

    #[Test]
    public function fromArrayWithNullRecordTypeReturnsFalseForAllTypeChecks(): void
    {
        $data = $this->data;
        unset($data['record_type']);
        $release = Release::fromArray($data);

        $this->assertFalse($release->isAlbum());
        $this->assertFalse($release->isSingle());
        $this->assertFalse($release->isEP());
    }
}
