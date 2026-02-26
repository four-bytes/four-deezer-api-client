# Four Deezer API Client

Modern PHP 8.4+ API client for the **Deezer Public API** with rate limiting and label caching.

## Features

- **No Authentication Required**: Deezer's public API is fully open
- **Genre-Based New Releases**: Get curated releases by genre (Metal, Rock, etc.)
- **Label Information**: Fetch album labels with built-in caching
- **Rate Limiting**: Integrated with `four-rate-limiting` (50 req/5s)
- **PSR Compliant**: PSR-6 (Cache), PSR-3 (Logger), PSR-17/18 (HTTP)
- **Immutable DTOs**: Type-safe readonly data objects

## Installation

```bash
composer require four-bytes/four-deezer-api-client
```

## Quick Start

```php
use Four\Deezer\DeezerClient;
use Four\Deezer\Endpoint\EditorialEndpoint;

// Create client (auto-discovers HTTP client)
$client = DeezerClient::create();

// Get new Metal releases
$releases = $client->editorial()->metalReleases();

foreach ($releases as $release) {
    echo sprintf(
        "%s - %s (%s)\n",
        $release->artist->name,
        $release->title,
        $release->releaseDate->format('Y-m-d')
    );
}
```

## Usage Examples

### Get Releases with Label Information

```php
use Four\Deezer\DeezerClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// With caching for labels (recommended)
$cache = new FilesystemAdapter('deezer', 604800, '/tmp/cache');
$client = DeezerClient::create($cache);

// Get releases from the last 7 days
$since = new DateTimeImmutable('-7 days');
$releases = $client->editorial()->releasesSince(
    EditorialEndpoint::GENRE_METAL,
    $since
);

// Get label for each release
foreach ($releases as $release) {
    $album = $client->albums()->get($release->id);
    
    echo sprintf(
        "%s - %s [%s] (%s)\n",
        $album->artist->name,
        $album->title,
        $album->label ?? 'Unknown Label',
        $album->releaseDate->format('Y-m-d')
    );
}
```

### Filter by Label

```php
// Get only Nuclear Blast releases
$releases = $client->editorial()->metalReleases(100);

$nuclearBlastReleases = [];
foreach ($releases as $release) {
    if ($client->albums()->isFromLabel($release->id, 'Nuclear Blast')) {
        $nuclearBlastReleases[] = $release;
    }
}
```

### Artist Albums

```php
// Get Nightwish albums (Artist ID: 1026)
$albums = $client->artists()->getAlbums(1026);

// Get new releases since a date
$newAlbums = $client->artists()->getNewReleases(
    1026,
    new DateTimeImmutable('-1 year')
);
```

### Search Artists

```php
$artists = $client->artists()->search('Nightwish');

foreach ($artists as $artist) {
    echo sprintf("%s (ID: %d)\n", $artist->name, $artist->id);
}
```

## Available Genres

The `EditorialEndpoint` provides constants for common genres:

| Constant | Genre | ID |
|----------|-------|-----|
| `GENRE_METAL` | Heavy Metal | 464 |
| `GENRE_ROCK` | Rock | 152 |
| `GENRE_ALTERNATIVE` | Alternative | 85 |
| `GENRE_POP` | Pop | 132 |
| `GENRE_RAP_HIPHOP` | Rap/Hip Hop | 116 |
| `GENRE_ELECTRO` | Electro | 106 |
| `GENRE_JAZZ` | Jazz | 129 |
| `GENRE_CLASSICAL` | Classical | 98 |

```php
// Get all available genres
$genres = $client->editorial()->getGenres();
```

## DTOs

All data is returned as immutable value objects:

### Release
Simplified album from editorial/releases (no label info):
- `id`, `title`, `artist`, `releaseDate`, `recordType`, `coverXl`

### Album
Full album details (includes label):
- `id`, `title`, `artist`, `label`, `releaseDate`, `upc`, `genres`, `tracks`

### Artist
- `id`, `name`, `link`, `pictureXl`, `nbFan`, `nbAlbum`

### Track
- `id`, `title`, `isrc`, `duration`, `artist`, `explicitLyrics`

## Rate Limiting

The client automatically integrates with `four-rate-limiting` if available:

- **Limit**: 50 requests per 5 seconds (10 req/sec)
- **Algorithm**: Sliding Window
- **Safety Buffer**: 80%

If `four-rate-limiting` is not installed, the client works without rate limiting.

## Caching

Label information is cached for 7 days when a PSR-6 cache is provided:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

// Filesystem cache
$cache = new FilesystemAdapter('deezer', 604800, '/tmp/cache');

// Or Redis cache
$redis = RedisAdapter::createConnection('redis://localhost');
$cache = new RedisAdapter($redis, 'deezer', 604800);

$client = DeezerClient::create($cache);
```

## Error Handling

```php
use Four\Deezer\Exception\NotFoundException;
use Four\Deezer\Exception\RateLimitException;
use Four\Deezer\Exception\ApiException;

try {
    $album = $client->albums()->get(999999999);
} catch (NotFoundException $e) {
    echo "Album not found: " . $e->getMessage();
} catch (RateLimitException $e) {
    echo "Rate limited, retry after: " . $e->getRetryAfterMs() . "ms";
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage();
}
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Related Libraries

- `four-bytes/four-rate-limiting` - Rate limiting with marketplace presets
- `four-bytes/four-http-client` - PSR-18 HTTP client with middleware support
