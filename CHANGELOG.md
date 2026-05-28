# Changelog

All notable changes to `growthatlas/laravel-connector` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.3.0] — 2026-05-28

### Added

- `GrowthAtlas\Connector\Filament\GrowthAtlasConnectorPlugin` — implements
  `Filament\Contracts\Plugin` so the Connector Status page is registered the
  correct Filament 4 way via `->plugin(GrowthAtlasConnectorPlugin::make())` in
  the application's panel provider.

### Fixed

- **[Blocker — Filament 4]** The Filament admin page was silently never registered.
  `ConnectorServiceProvider` used `callAfterResolving('filament', ...)` + `$panel->pages()`
  which fires after Filament 4 has already booted its panels, so the page was never
  added. A bare `catch (\Throwable)` block swallowed the failure with no indication.
  Replaced with a proper `FilamentPlugin` implementation.

### Removed

- Auto-registration of the Filament page from `ConnectorServiceProvider` — this never
  worked reliably in Filament 4 and the `catch (\Throwable)` masked the failure.
- `filament_page` and `filament_panel_id` config keys — registration is now fully
  explicit via the plugin (see README).
- `GROWTHATLAS_FILAMENT` env variable — no longer used.

### Migration guide (v1.2.0 → v1.3.0)

Remove `GROWTHATLAS_FILAMENT=true` from your `.env` (or leave it — it is harmless
but unused). Then register the plugin in your Filament panel provider:

```php
use GrowthAtlas\Connector\Filament\GrowthAtlasConnectorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(GrowthAtlasConnectorPlugin::make())
        // ...
}
```

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
