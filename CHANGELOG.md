# Changelog

All notable changes to `growthatlas/laravel-connector` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed
- Idempotency check on `POST /content-drafts` now works correctly for models that use
  `protected $guarded = []` instead of `$fillable`. Previously the `growthatlas_draft_id`
  check was skipped for such models, causing duplicate records.

### Added
- `README.md` — full installation guide, config reference, wiring examples,
  security checklist, and a machine-readable **Integration Recipe for AI assistants**.
- `LICENSE` file.
- `CHANGELOG.md`.
- Test suite (`tests/`) using Orchestra Testbench.
- `phpunit.xml` configuration.

---

## [1.0.0] — 2026-05-27

### Added

- Initial release published to Packagist.
- Five Connector API v1 endpoints: `GET /health`, `GET /site-profile`, `GET /pages`,
  `GET /entities`, `POST /content-drafts`.
- `AuthenticateGrowthAtlas` middleware: Bearer token (constant-time) + optional
  HMAC-SHA256 signature verification in a single middleware.
- Config-driven field mapping for content draft publishing (`publishing.fields`).
- Config-driven entity export (`entities` type → model map).
- Eloquent page export (`pages.source = "eloquent"`).
- `ConnectorServiceProvider` with auto-discovery and publish tags for config, views,
  and Filament page.
