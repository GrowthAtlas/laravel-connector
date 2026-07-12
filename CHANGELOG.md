# Changelog

All notable changes to `growthatlas/laravel-connector` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed

- **Admin UI styling.** Status cards, Content from GrowthAtlas table, and the
  Test connection modal now use self-contained CSS so layout works even when the
  host app does not compile this package’s Tailwind classes.
- **`GET /health` without API key.** Returns HTTP `503` when no API key is
  configured (admin page or `GROWTHATLAS_API_KEY`), so connection tests fail
  until the connector is ready.
- **Unresponsive header actions.** Page `<style>` must live inside
  `<x-filament-panels::page>` so Livewire keeps a single root; styles before the
  page root broke Filament header buttons (Test connection, Set API key, etc.).

---

## [1.7.0] — 2026-07-12

### Added

- **`publishing.url_prefix`.** Optional path prefix (e.g. `blog`) prepended to the
  slug when building the public URL returned to GrowthAtlas after create/update.
  Fixes sites whose posts live under `/blog/{slug}` (or similar) instead of the
  site root. When set, the prefix takes precedence over `Model::getUrl()`.
  Configure via `GROWTHATLAS_URL_PREFIX` or published config.

### Changed

- `GET /health` reports `connector_version: 1.7.0`.

---

## [1.6.0] — 2026-07-02

### Added

- **Content updates ("refresh").** New `PUT|PATCH /content-drafts/{externalId}`
  endpoint updates an already-published post in place instead of creating a
  duplicate. Resolves the target by external id, falling back to
  `growthatlas_draft_id`; if nothing matches it creates the post so an update is
  never silently lost. `GET /health` now advertises `supports_update: true`.
- **Database-managed settings.** The API key, HMAC signing secret and request
  logging flag can now be set, rotated and toggled from the Filament admin page
  — no `.env` editing required. Stored in the new `growthatlas_settings` table;
  a saved value overrides the matching `.env`/config fallback.
- **Received-content tracking.** Every article created or updated by GrowthAtlas
  is recorded in the new `growthatlas_received_content` table and listed on the
  admin page with links to view it on the site and open the originating draft in
  GrowthAtlas (`growthatlas_url` payload field).

### Changed

- **Admin page redesign.** Status cards for API key / signing / logging (showing
  whether each is managed here or from `.env`), a merged "Connection endpoint"
  section with a copy button, an in-page **Test connection** modal (replaces the
  "open /health in a new tab" button), a "Content from GrowthAtlas" table, and
  the recent-requests audit table.
- Request-logging middleware is now always registered and decides at runtime
  from the (DB-managed) `log_inbound` setting.
- `GET /health` reports `connector_version: 1.6.0`.

### Migration guide (v1.5.0 → v1.6.0)

Publish and run the new migrations:

```bash
php artisan vendor:publish --tag=growthatlas-connector-migrations
php artisan migrate
```

Existing `.env` values keep working as defaults. To manage credentials from the
admin page instead, open **Integrations → GrowthAtlas** and use *Set API key* /
*Signing secret* / *Enable request logging*.

---

## [1.5.0] — 2026-05-28

### Fixed

- **[Bug 1 — High]** Published posts returned 404 on sites that require a non-null
  `published_at` to show a post publicly. The connector set the `status` column but never
  wrote a timestamp. `createContentDraft` now automatically sets `published_at = now()`
  when `publish_status` is `"published"` and `publishing.published_at_column` is
  configured. The column defaults to `"published_at"`; set it to `null` to disable the
  behaviour (e.g. if your model sets it via an observer).

- **[Bug 2 — Medium]** Default config `publishing.fields` did not include
  `featured_image_url` / `featured_image_alt`, so featured images were silently dropped.
  Both fields are now present as commented-out examples with an inline note warning
  integrators **not** to pass the value through `Storage::url()` (which corrupts an
  absolute CDN URL by prepending `/storage/`).

### Changed

- `GET /health` now reports `connector_version: 1.5.0`.
- `publishing.fields` now documents all available GrowthAtlas payload fields
  in a comment above the map.

### Migration guide (v1.4.0 → v1.5.0)

Re-publish the config to pick up the new keys:

```bash
php artisan vendor:publish --tag=growthatlas-config --force
```

If you do **not** want the connector to auto-set `published_at` (e.g. your model
already handles it via an observer), add this to your published config:

```php
'published_at_column' => null,
```

To enable featured image imports, uncomment the relevant lines in the `fields` map:

```php
'featured_image_url' => 'featured_image_path', // or whatever column stores the URL
'featured_image_alt' => 'featured_image_alt',
```

> **Note:** `featured_image_url` is always an absolute CDN URL
> (`https://images.unsplash.com/...`). Do **not** pass it through `Storage::url()` —
> check `str_starts_with($value, 'http')` and use the value as-is.

---

## [1.4.0] — 2026-05-28

### Fixed

- **[Blocker — Filament 4]** `connector-status.blade.php` was written with hand-rolled
  HTML and raw Tailwind utility classes. Filament pre-compiles its CSS bundle by scanning
  only its own source files, so arbitrary vendor-view classes are not guaranteed to be
  present. In particular `text-[10px]` (a JIT arbitrary-value class) was never compiled,
  causing the page to render with partial styling only.

  Rewritten to use Filament's own component primitives exclusively:
  - Three status indicators now use `<x-filament::section>` + `<x-filament::badge>` with
    named color tokens (`success`, `danger`, `info`, `gray`) — no custom colour classes.
  - HTTP status-code badges in the recent-requests table now use `<x-filament::badge>`
    instead of a hand-rolled `<span class="... text-[10px] ...">`.
  - Signature-validity column uses `<x-filament::badge>` with text labels instead of
    raw Unicode tick/cross characters.
  - All outer `<div>` wrappers replaced by `<x-filament::section>` (heading + description
    slots), which handles dark mode and spacing through Filament's own design tokens.
  - The `<table>` itself is kept as plain HTML but uses only Tailwind scale utilities
    (`text-sm`, `font-mono`, `px-3`, `py-2`, `divide-y`, etc.) that are guaranteed to
    be in Filament's compiled bundle.

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
