# Changelog

All notable changes to `growthatlas/laravel-connector` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.2.0] — 2026-05-28

### Fixed

- **[Blocker]** `composer.json` illuminate constraints (`illuminate/support`, `illuminate/http`,
  `illuminate/routing`) now include `^13.0` so the package installs on Laravel 13.
  `orchestra/testbench` dev-dependency extended to `^10.0` to cover Laravel 13 in CI.
- **[Blocker]** Route prefix was applied twice — once in `ConnectorServiceProvider::registerRoutes()`
  and again inside `routes/api.php`. All endpoint URLs were doubled
  (e.g. `api/growthatlas/v1/api/growthatlas/v1/health`). `routes/api.php` no longer
  re-reads the prefix; the service provider is the sole owner of the prefix.
- **[Blocker — Filament 4]** `ConnectorStatus::$navigationGroup` typed `?string` but
  Filament 4 declares the parent property as `string|UnitEnum|null`. PHP property
  invariance caused a fatal error at class-load time. Type updated to
  `string|\UnitEnum|null`.
- **[Blocker — Filament 4]** `ConnectorStatus::$navigationIcon` typed `?string` but
  Filament 4 declares the parent property as `string|BackedEnum|null`. Type updated to
  `string|\BackedEnum|null`.
- **[Blocker — Filament 4]** `ConnectorStatus::$view` declared `static` but Filament 4
  changed the parent property to a non-static instance property. `static` keyword
  removed.

### Changed

- Previous unreleased fix: idempotency check on `POST /content-drafts` now works correctly
  for models using `protected $guarded = []` instead of `$fillable`.

---

## [1.1.0] — 2026-05-27

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
