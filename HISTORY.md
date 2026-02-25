# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-07

### Added
- Initial release
- `DeezerClient` with PSR-17/18 HTTP client support
- `EditorialEndpoint` for genre-based new releases
- `AlbumEndpoint` with label caching (PSR-6)
- `ArtistEndpoint` for artist albums and search
- DTOs: `Album`, `Artist`, `Release`, `Track`, `Genre`
- Rate limiting integration with `four-rate-limiting`
- Label caching with 7-day TTL
- Support for Metal genre (ID 464) as default
- Exception hierarchy: `DeezerException`, `NotFoundException`, `RateLimitException`, `ApiException`

## [1.0.1] - 2026-02-25

### Fixed
- PHPStan Level 8: 19 Fehler behoben (Type-Annotations, unused constants)
- PHP_CodeSniffer PSR-12: 57 CS-Fehler behoben (Trailing Whitespace, Brace-Formatierung)

### Added
- CLAUDE.md mit Projektübersicht und Entwicklungs-Konventionen

## [1.1.0] - 2026-02-25

### Added
- PHPUnit 11 Test-Suite: 84 Tests, 220 Assertions (DTO, Exception, DeezerClient)
- Tests für alle DTOs: Album, Artist, Track, Genre, Release
- Tests für Exception-Hierarchie: RateLimitException, ApiException, NotFoundException
- DeezerClient-Tests mit Guzzle MockHandler (429-Handling, Error-Body, JSON-Fehler)

### Fixed
- phpunit.xml auf aktuelles Schema migriert (Deprecation-Warning entfernt)
