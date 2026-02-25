<?php

declare(strict_types=1);

namespace Four\Deezer\Tests\Unit\DTO;

use Four\Deezer\DTO\Artist;
use Four\Deezer\DTO\Track;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Track::class)]
final class TrackTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $data;

    protected function setUp(): void
    {
        $this->data = [
            'id' => 789456,
            'title' => 'Élan',
            'title_short' => 'Élan',
            'isrc' => 'FI0W41500028',
            'link' => 'https://www.deezer.com/track/789456',
            'duration' => 289,
            'track_position' => 2,
            'disk_number' => 1,
            'rank' => 876543,
            'explicit_lyrics' => false,
            'preview' => 'https://cdns-preview-a.dzcdn.net/stream/c-123.mp3',
            'artist' => [
                'id' => 1026,
                'name' => 'Nightwish',
            ],
            'album' => [
                'id' => 123,
                'title' => 'Endless Forms Most Beautiful',
            ],
            'release_date' => '2015-03-27',
        ];
    }

    #[Test]
    public function fromArrayCreatesTrack(): void
    {
        $track = Track::fromArray($this->data);

        $this->assertInstanceOf(Track::class, $track);
        $this->assertSame(789456, $track->id);
        $this->assertSame('Élan', $track->title);
        $this->assertSame('Élan', $track->titleShort);
        $this->assertSame('FI0W41500028', $track->isrc);
        $this->assertSame('https://www.deezer.com/track/789456', $track->link);
        $this->assertSame(289, $track->duration);
        $this->assertSame(2, $track->trackPosition);
        $this->assertSame(1, $track->diskNumber);
        $this->assertSame(876543, $track->rank);
        $this->assertFalse($track->explicitLyrics);
        $this->assertSame('https://cdns-preview-a.dzcdn.net/stream/c-123.mp3', $track->preview);
        $this->assertSame(123, $track->albumId);
    }

    #[Test]
    public function fromArrayCreatesCorrectArtist(): void
    {
        $track = Track::fromArray($this->data);

        $this->assertInstanceOf(Artist::class, $track->artist);
        $this->assertSame(1026, $track->artist->id);
        $this->assertSame('Nightwish', $track->artist->name);
    }

    #[Test]
    public function fromArrayReleaseDateIsDateTimeImmutable(): void
    {
        $track = Track::fromArray($this->data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $track->releaseDate);
        $this->assertSame('2015-03-27', $track->releaseDate->format('Y-m-d'));
    }

    #[Test]
    public function fromArrayWithoutReleaseDateReturnsNull(): void
    {
        $data = $this->data;
        unset($data['release_date']);

        $track = Track::fromArray($data);

        $this->assertNull($track->releaseDate);
    }

    #[Test]
    public function fromArrayWithEmptyReleaseDateReturnsNull(): void
    {
        $data = $this->data;
        $data['release_date'] = '';

        $track = Track::fromArray($data);

        $this->assertNull($track->releaseDate);
    }

    #[Test]
    public function fromArrayWithoutAlbumIdReturnsNull(): void
    {
        $data = $this->data;
        unset($data['album']);

        $track = Track::fromArray($data);

        $this->assertNull($track->albumId);
    }

    #[Test]
    public function fromArrayFallsTitleShortToTitle(): void
    {
        $data = $this->data;
        unset($data['title_short']);

        $track = Track::fromArray($data);

        $this->assertSame('Élan', $track->titleShort);
    }

    #[Test]
    public function fromArrayUsesDefaultsForOptionalFields(): void
    {
        $minimalData = [
            'id' => 1,
            'title' => 'Minimal Track',
            'link' => 'https://www.deezer.com/track/1',
        ];

        $track = Track::fromArray($minimalData);

        $this->assertSame(0, $track->duration);
        $this->assertSame(1, $track->trackPosition);
        $this->assertSame(1, $track->diskNumber);
        $this->assertSame(0, $track->rank);
        $this->assertFalse($track->explicitLyrics);
        $this->assertNull($track->preview);
        $this->assertNull($track->isrc);
    }

    #[Test]
    public function toArrayReturnsCorrectFields(): void
    {
        $track = Track::fromArray($this->data);
        $result = $track->toArray();

        $this->assertSame(789456, $result['id']);
        $this->assertSame('Élan', $result['title']);
        $this->assertSame('FI0W41500028', $result['isrc']);
        $this->assertSame(289, $result['duration']);
        $this->assertSame('https://www.deezer.com/track/789456', $result['link']);
        $this->assertIsArray($result['artist']);
    }

    #[Test]
    public function getDurationFormattedReturnsMinutesAndSeconds(): void
    {
        $track = Track::fromArray($this->data);

        // 289 Sekunden = 4:49
        $this->assertSame('4:49', $track->getDurationFormatted());
    }

    #[Test]
    public function getDurationFormattedWithZeroSeconds(): void
    {
        $data = $this->data;
        $data['duration'] = 0;

        $track = Track::fromArray($data);

        $this->assertSame('0:00', $track->getDurationFormatted());
    }

    #[Test]
    public function getDurationFormattedPadsSecondsCorrectly(): void
    {
        $data = $this->data;
        $data['duration'] = 65; // 1:05

        $track = Track::fromArray($data);

        $this->assertSame('1:05', $track->getDurationFormatted());
    }
}
