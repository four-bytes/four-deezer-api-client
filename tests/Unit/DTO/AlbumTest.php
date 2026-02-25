<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\DTO;

use Four\Deezer\DTO\Album;
use Four\Deezer\DTO\Artist;
use Four\Deezer\DTO\Genre;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Album::class)]
final class AlbumTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        $this->data = [
            'id' => 123,
            'title' => 'Endless Forms Most Beautiful',
            'artist' => [
                'id' => 1026,
                'name' => 'Nightwish',
                'link' => 'https://deezer.com/artist/1026',
                'picture_xl' => null,
                'nb_fan' => 0,
                'nb_album' => 0,
            ],
            'label' => 'Nuclear Blast',
            'release_date' => '2015-03-27',
            'link' => 'https://deezer.com/album/123',
            'upc' => '727361349134',
            'nb_tracks' => 13,
            'duration' => 3948,
            'fans' => 50000,
            'explicit_lyrics' => false,
            'record_type' => 'album',
            'cover_xl' => 'https://cdn.deezer.com/images/cover_xl.jpg',
            'genres' => [
                'data' => [
                    ['id' => 464, 'name' => 'Metal', 'picture' => null],
                ],
            ],
            'tracks' => ['data' => []],
            'contributors' => [],
        ];
    }

    #[Test]
    public function fromArrayCreatesAlbum(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertInstanceOf(Album::class, $album);
        $this->assertSame(123, $album->id);
        $this->assertSame('Endless Forms Most Beautiful', $album->title);
        $this->assertSame('Nuclear Blast', $album->label);
        $this->assertSame('https://deezer.com/album/123', $album->link);
        $this->assertSame('727361349134', $album->upc);
        $this->assertSame(13, $album->nbTracks);
        $this->assertSame(3948, $album->duration);
        $this->assertSame(50000, $album->fans);
        $this->assertFalse($album->explicitLyrics);
        $this->assertSame('album', $album->recordType);
        $this->assertSame('https://cdn.deezer.com/images/cover_xl.jpg', $album->coverXl);
    }

    #[Test]
    public function fromArrayCreatesCorrectArtist(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertInstanceOf(Artist::class, $album->artist);
        $this->assertSame(1026, $album->artist->id);
        $this->assertSame('Nightwish', $album->artist->name);
    }

    #[Test]
    public function fromArrayReleaseDateIsDateTimeImmutable(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $album->releaseDate);
        $this->assertSame('2015-03-27', $album->releaseDate->format('Y-m-d'));
    }

    #[Test]
    public function fromArrayGenresIsArrayOfGenreObjects(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertCount(1, $album->genres);
        $this->assertInstanceOf(Genre::class, $album->genres[0]);
        $this->assertSame(464, $album->genres[0]->id);
        $this->assertSame('Metal', $album->genres[0]->name);
    }

    #[Test]
    public function fromArrayWithoutGenresReturnsEmptyArray(): void
    {
        $data = $this->data;
        unset($data['genres']);

        $album = Album::fromArray($data);

        $this->assertSame([], $album->genres);
    }

    #[Test]
    public function toArrayReturnsCorrectFields(): void
    {
        $album = Album::fromArray($this->data);
        $result = $album->toArray();

        $this->assertSame(123, $result['id']);
        $this->assertSame('Endless Forms Most Beautiful', $result['title']);
        $this->assertSame('Nuclear Blast', $result['label']);
        $this->assertSame('2015-03-27', $result['release_date']);
        $this->assertSame('https://deezer.com/album/123', $result['link']);
        $this->assertSame('727361349134', $result['upc']);
        $this->assertSame(13, $result['nb_tracks']);
        $this->assertSame('album', $result['record_type']);
        $this->assertSame('https://cdn.deezer.com/images/cover_xl.jpg', $result['cover_xl']);
        $this->assertIsArray($result['artist']);
        $this->assertIsArray($result['genres']);
    }

    #[Test]
    public function isFromLabelReturnsTrueForKnownLabel(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertTrue($album->isFromLabel('Nuclear Blast'));
    }

    #[Test]
    public function isFromLabelIsCaseInsensitive(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertTrue($album->isFromLabel('nuclear blast'));
        $this->assertTrue($album->isFromLabel('NUCLEAR'));
    }

    #[Test]
    public function isFromLabelReturnsFalseForUnknownLabel(): void
    {
        $album = Album::fromArray($this->data);

        $this->assertFalse($album->isFromLabel('Sony'));
    }

    #[Test]
    public function isFromLabelReturnsFalseWhenLabelIsNull(): void
    {
        $data = $this->data;
        $data['label'] = null;
        $album = Album::fromArray($data);

        $this->assertFalse($album->isFromLabel('Nuclear Blast'));
    }

    #[Test]
    public function getDurationFormattedReturnsCorrectTime(): void
    {
        $album = Album::fromArray($this->data);

        // 3948 Sekunden = 1h 5min 48s → '1:05:48'
        $this->assertSame('1:05:48', $album->getDurationFormatted());
    }

    #[Test]
    public function getDurationFormattedWithoutHoursReturnsMinutesSeconds(): void
    {
        $data = $this->data;
        $data['duration'] = 245; // 4:05

        $album = Album::fromArray($data);

        $this->assertSame('4:05', $album->getDurationFormatted());
    }

    #[Test]
    public function getDurationFormattedWithZeroSeconds(): void
    {
        $data = $this->data;
        $data['duration'] = 0;

        $album = Album::fromArray($data);

        $this->assertSame('0:00', $album->getDurationFormatted());
    }

    #[Test]
    public function fromArrayWithContributorsInsteadOfArtist(): void
    {
        $data = $this->data;
        unset($data['artist']);
        $data['contributors'] = [
            ['id' => 999, 'name' => 'Floor Jansen'],
        ];

        $album = Album::fromArray($data);

        $this->assertSame(999, $album->artist->id);
        $this->assertSame('Floor Jansen', $album->artist->name);
        $this->assertCount(1, $album->contributors);
    }
}
