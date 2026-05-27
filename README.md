# GrowthAtlas Laravel Connector

[![Latest Version on Packagist](https://img.shields.io/packagist/v/growthatlas/laravel-connector.svg?style=flat-square)](https://packagist.org/packages/growthatlas/laravel-connector)
[![PHP Version](https://img.shields.io/packagist/php-v/growthatlas/laravel-connector.svg?style=flat-square)](https://packagist.org/packages/growthatlas/laravel-connector)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red.svg?style=flat-square)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

The official Laravel package for connecting any Laravel application to [GrowthAtlas](https://growthatlas.io).

Install it on your Laravel site and GrowthAtlas can:

- **Push AI-generated SEO content** directly into your `Post`, `Article`, or any Eloquent model.
- **Pull your existing pages** for SEO gap analysis and internal linking.
- **Import your entities** (products, categories, locations) for topical authority clustering.
- **Close the performance loop** using Google Search Console data from the GrowthAtlas dashboard.

The package exposes five endpoints under `/api/growthatlas/v1/` that implement the [Connector API v1 specification](https://growthatlas.io/connector-api). All endpoints are secured with a Bearer token and optionally an HMAC-SHA256 signature. You configure which Eloquent models back the responses — no forking required.

---

## What it does

```
GrowthAtlas dashboard
        │
        │  POST /api/growthatlas/v1/content-drafts  ← AI article payload
        │  GET  /api/growthatlas/v1/pages            ← your published pages
        │  GET  /api/growthatlas/v1/entities         ← products, categories…
        │  GET  /api/growthatlas/v1/site-profile     ← site metadata
        │  GET  /api/growthatlas/v1/health           ← connection test
        ▼
  Your Laravel app
        │
        ▼
  App\Models\Post  (or any model you configure)
```

**Autopilot publishing flow:**

1. GrowthAtlas generates an SEO article based on your keyword strategy.
2. It `POST`s the article to `/api/growthatlas/v1/content-drafts` on your site.
3. The connector validates the Bearer token and writes the article into your configured Eloquent model.
4. The record appears in your database as a draft, ready for your review or auto-published.
5. GrowthAtlas marks the content as published and tracks GSC performance.

---

## Requirements

| Requirement     | Version                  |
|-----------------|--------------------------|
| PHP             | ^8.1                     |
| Laravel         | ^10.0, ^11.0, or ^12.0   |
| Filament (opt.) | ^3.0 or ^4.0             |

---

## Install

### 1. Require the package

```bash
composer require growthatlas/laravel-connector
```

The package registers itself automatically via Laravel's package auto-discovery. No manual provider registration needed.

### 2. Publish the config file

```bash
php artisan vendor:publish --tag=growthatlas-connector-config
```

This creates `config/growthatlas-connector.php` in your application.

### 3. Add your API key to `.env`

Go to your GrowthAtlas dashboard → **Integrations** → **New Integration** → select **Laravel**. Copy the generated API key:

```dotenv
GROWTHATLAS_API_KEY=ga_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Quick start (5 minutes)

### Step 1 — Tell the connector which model receives drafts

Open `config/growthatlas-connector.php`. Point `publishing.model` at your model and map the columns:

```php
'publishing' => [
    'model' => App\Models\Post::class,

    'fields' => [
        'title'            => 'title',
        'slug'             => 'slug',
        'body'             => 'body',          // or 'content' — whatever your column is
        'meta_description' => 'meta_description',
    ],

    'status_column'          => 'status',
    'growthatlas_id_column'  => 'growthatlas_draft_id',   // add this column (see below)
    'status_map' => [
        'draft'     => 'draft',
        'published' => 'published',
    ],
],
```

### Step 2 — Add the idempotency column

The connector uses `growthatlas_draft_id` to detect duplicate pushes and skip re-creating them.

```bash
php artisan make:migration add_growthatlas_draft_id_to_posts_table
```

```php
// In the migration up() method:
$table->unsignedBigInteger('growthatlas_draft_id')->nullable()->unique();
```

```bash
php artisan migrate
```

### Step 3 — Clear caches

```bash
php artisan config:clear
php artisan route:clear
```

### Step 4 — Verify the connection

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/api/growthatlas/v1/health
```

Expected response:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "connector": "laravel",
    "connector_version": "1.0.0",
    "platform": "laravel",
    "growthatlas_api_version": "v1"
  }
}
```

Then click **Test Connection** in the GrowthAtlas dashboard. A green tick means you're live.

---

## Configuration reference

`config/growthatlas-connector.php` — published by `vendor:publish --tag=growthatlas-connector-config`.

| Key | Default | Purpose |
|-----|---------|---------|
| `api_key` | `env('GROWTHATLAS_API_KEY')` | Bearer token GrowthAtlas sends on every request. **Required.** |
| `signing_secret` | `env('GROWTHATLAS_SIGNING_SECRET')` | Optional shared secret for HMAC-SHA256 signature verification. |
| `route_prefix` | `"api/growthatlas/v1"` | Full URL prefix for all connector routes. |
| `route_middleware` | `["api"]` | Laravel middleware group applied to connector routes. |
| `publishing.model` | `App\Models\Post` | Eloquent model that receives content drafts. |
| `publishing.fields` | see config | Column map: GrowthAtlas field → your column name. |
| `publishing.status_column` | `"status"` | Column that stores the publish status. |
| `publishing.status_map` | `{draft: draft, published: published}` | Maps GrowthAtlas status values to your model's status values. |
| `publishing.growthatlas_id_column` | `"growthatlas_draft_id"` | Idempotency column — must be `unique` indexed. |
| `pages.source` | `"eloquent"` | How pages are fetched: `"eloquent"` or `"sitemap"`. |
| `pages.model` | `App\Models\Post` | Eloquent model used when `source = "eloquent"`. |
| `pages.url_column` | `"slug"` | Column containing the page URL or slug. |
| `entities` | `[]` | Map of `type => ModelClass` for the `/entities` endpoint. |
| `filament_page` | `false` | Whether to register the Filament ConnectorStatus admin page. |

### Environment variables

```dotenv
# Required
GROWTHATLAS_API_KEY=ga_live_xxxxxxxxxxxxxxxx

# Optional — HMAC signature verification (recommended for production)
GROWTHATLAS_SIGNING_SECRET=

# Optional — change the default publish model
GROWTHATLAS_PUBLISH_MODEL=App\Models\Post
```

---

## Wiring your own model

### Case 1 — Standard blog (`App\Models\Post`, columns: title, slug, body, status)

```php
'publishing' => [
    'model'   => App\Models\Post::class,
    'fields'  => [
        'title'            => 'title',
        'slug'             => 'slug',
        'body'             => 'body',
        'meta_description' => 'meta_description',
    ],
    'status_column'         => 'status',
    'growthatlas_id_column' => 'growthatlas_draft_id',
    'status_map' => ['draft' => 'draft', 'published' => 'published'],
],
'pages' => [
    'source'     => 'eloquent',
    'model'      => App\Models\Post::class,
    'url_column' => 'slug',
],
```

### Case 2 — Filament app with `App\Models\Article` (body column is `content`)

```php
'publishing' => [
    'model'   => App\Models\Article::class,
    'fields'  => [
        'title'            => 'title',
        'slug'             => 'slug',
        'body'             => 'content',        // different column name
        'meta_description' => 'seo_description',
    ],
    'status_column'         => 'status',
    'growthatlas_id_column' => 'growthatlas_draft_id',
    'status_map' => ['draft' => 0, 'published' => 1],  // boolean status
],
```

### Case 3 — Expose products and categories via `/entities`

```php
'entities' => [
    'product'  => App\Models\Product::class,
    'category' => App\Models\Category::class,
],
```

Each model should have `name`, `slug`, `description`, and `url` (or `slug`) columns — the connector reads them automatically. For different column names, override the response in a custom controller action (see Custom Behaviour below).

---

## Security

### Bearer token authentication

Every request from GrowthAtlas includes:

```
Authorization: Bearer <your-api-key>
```

The connector verifies this using `hash_equals()` (constant-time comparison) to prevent timing attacks.

### HMAC-SHA256 signatures (optional, recommended for production)

When `signing_secret` is set, the connector also verifies the `X-GrowthAtlas-Signature` header:

```dotenv
GROWTHATLAS_SIGNING_SECRET=your-64-char-random-secret
```

Copy this same value into the **Signing Secret** field in your GrowthAtlas integration settings. GrowthAtlas will then send:

```
X-GrowthAtlas-Signature: sha256=<hex-digest>
```

where the digest is `HMAC-SHA256(secret, raw_request_body)`. The connector verifies this automatically. **When the signing secret is set, requests without a matching signature are rejected with 401.**

### Security checklist

- [ ] Use HTTPS on your domain — GrowthAtlas never sends to plain HTTP in production.
- [ ] Set a strong, unique `GROWTHATLAS_API_KEY` (min 32 chars, generated randomly).
- [ ] Set `GROWTHATLAS_SIGNING_SECRET` to a 64-char random string for HMAC verification.
- [ ] Add a `unique` index on `growthatlas_draft_id` in your model migration.
- [ ] Run `php artisan config:clear` after changing `.env` values.

---

## Routes registered

After installation, run `php artisan route:list | grep growthatlas` to confirm:

| Method | URI | Description |
|--------|-----|-------------|
| `GET` | `api/growthatlas/v1/health` | Connection test — no auth required |
| `GET` | `api/growthatlas/v1/site-profile` | Site metadata |
| `GET` | `api/growthatlas/v1/pages` | Paginated page list |
| `GET` | `api/growthatlas/v1/entities` | Paginated entity list |
| `POST` | `api/growthatlas/v1/content-drafts` | Receive content draft |

The prefix is configurable via `route_prefix` in the config.

---

## Filament admin page (optional)

Enable the built-in Filament status page by setting `filament_page => true` in the config and then publishing the Filament page class:

```bash
php artisan vendor:publish --tag=growthatlas-connector-filament
```

This copies the page class into your `app/Filament/Pages/` directory. Requires `filament/filament` to be installed.

---

## Troubleshooting

### `401 Unauthorized` on all endpoints

- Confirm `GROWTHATLAS_API_KEY` in `.env` exactly matches the key in the GrowthAtlas dashboard (no trailing spaces).
- Run `php artisan config:clear` after any `.env` change.

### `404 Not Found` on `/api/growthatlas/v1/health`

- Run `php artisan route:list | grep growthatlas` — if no routes appear, the service provider wasn't registered. Check that your app has package auto-discovery enabled (most do by default).
- If you use route caching: `php artisan route:clear && php artisan route:cache`.
- Confirm `route_prefix` in config matches how you're calling the URL.

### `500` on `POST /content-drafts`

- The `growthatlas_draft_id` column may be missing. Run the migration.
- `publishing.model` class doesn't exist or isn't auto-loadable. Check the FQCN.

### Draft created twice (no idempotency)

- The `growthatlas_draft_id` column isn't in the model's `$fillable` array. Add it.
- The column exists but doesn't have a `unique` index. Add one in a migration.

### Signature verification fails (401 after setting `signing_secret`)

- The secret in `.env` must match the **Signing Secret** field in GrowthAtlas exactly.
- Run `php artisan config:clear`.
- Ensure no middleware is modifying the raw request body before the connector middleware runs.

---

## Testing the connection locally

### Via curl

```bash
# Health check
curl -H "Authorization: Bearer YOUR_API_KEY" \
     http://localhost/api/growthatlas/v1/health

# Simulate a draft push
curl -X POST http://localhost/api/growthatlas/v1/content-drafts \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "growthatlas_draft_id": 9999,
    "title": "Test Article",
    "slug": "test-article",
    "body": "## Hello\n\nThis is a test.",
    "meta_description": "A test draft.",
    "publish_status": "draft",
    "source": "growthatlas"
  }'
```

Expected response for first call (`created: true`):
```json
{"success":true,"data":{"external_id":"1","url":"http://localhost/test-article","status":"draft","created":true}}
```

Run again with the same `growthatlas_draft_id` to verify idempotency — `created` will be `false`.

---

## Integration Recipe (for AI assistants)

> **This section is a machine-readable step-by-step guide.** If you are an AI assistant (ChatGPT, Claude, Gemini, Copilot, Cursor, etc.) helping a developer integrate this package into a Laravel application, follow these steps in order. Each step is independently verifiable.

### Prerequisites

- PHP 8.1+
- Laravel 10, 11, or 12
- Composer installed
- A GrowthAtlas account at https://growthatlas.io with a Laravel integration created

---

### Step 1 — Install

```bash
composer require growthatlas/laravel-connector
```

**Verify:** `composer show growthatlas/laravel-connector` prints the version. Package auto-discovers `GrowthAtlas\Connector\ConnectorServiceProvider`.

---

### Step 2 — Publish config

```bash
php artisan vendor:publish --tag=growthatlas-connector-config
```

**Verify:** `config/growthatlas-connector.php` exists.

---

### Step 3 — Set environment variable

```dotenv
GROWTHATLAS_API_KEY=<copy from GrowthAtlas dashboard → Integrations → your integration → API Key>
```

**Verify:** `php artisan tinker --execute="echo config('growthatlas-connector.api_key');"` prints the key.

---

### Step 4 — Identify the target model

Ask the developer which Eloquent model stores blog posts/articles and what the column names are for:
- title
- slug (URL identifier)
- body content
- meta description
- publish status

---

### Step 5 — Add idempotency column

```bash
php artisan make:migration add_growthatlas_draft_id_to_posts_table
```

In `up()`:

```php
$table->unsignedBigInteger('growthatlas_draft_id')->nullable()->unique();
```

Also add `'growthatlas_draft_id'` to the model's `$fillable` array.

```bash
php artisan migrate
```

**Verify:** `php artisan tinker --execute="Schema::hasColumn('posts', 'growthatlas_draft_id');"` returns `true`.

---

### Step 6 — Configure `config/growthatlas-connector.php`

Set `publishing.model` to the target model FQCN. Map `publishing.fields` keys to actual column names. Example for `App\Models\Post`:

```php
'publishing' => [
    'model'   => App\Models\Post::class,
    'fields'  => [
        'title'            => 'title',
        'slug'             => 'slug',
        'body'             => 'body',           // adjust if column is 'content'
        'meta_description' => 'meta_description',
    ],
    'status_column'         => 'status',
    'growthatlas_id_column' => 'growthatlas_draft_id',
    'status_map' => ['draft' => 'draft', 'published' => 'published'],
],
'pages' => [
    'source'     => 'eloquent',
    'model'      => App\Models\Post::class,
    'url_column' => 'slug',    // or 'url' if the model stores full URLs
],
```

---

### Step 7 — Clear caches

```bash
php artisan config:clear
php artisan route:clear
```

---

### Step 8 — Verify health endpoint

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/api/growthatlas/v1/health
```

**Expected:**
```json
{"success":true,"data":{"status":"ok","connector":"laravel","growthatlas_api_version":"v1"}}
```

If `401`: recheck `GROWTHATLAS_API_KEY` and run `php artisan config:clear`.
If `404`: run `php artisan route:list | grep growthatlas` to confirm routes loaded.

---

### Step 9 — Test draft ingestion

```bash
curl -X POST https://yoursite.com/api/growthatlas/v1/content-drafts \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"growthatlas_draft_id":1,"title":"Hello GrowthAtlas","slug":"hello-growthatlas","body":"Test.","publish_status":"draft","source":"growthatlas"}'
```

**Expected:** `{"success":true,"data":{"external_id":"1","status":"draft","created":true}}`

Confirm a row exists in the database with `growthatlas_draft_id = 1`.

---

### Step 10 — Test in GrowthAtlas dashboard

Go to **Integrations**, find the integration, click **Test Connection**. Green tick = connected.

---

### Troubleshooting quick-reference for AI assistants

| Symptom | Most likely cause | Fix |
|---------|-------------------|-----|
| `401` everywhere | `GROWTHATLAS_API_KEY` mismatch | Check `.env`, `php artisan config:clear` |
| `404` on all routes | Service provider not loaded | Check auto-discovery; `php artisan route:list \| grep growthatlas` |
| `500` on `/content-drafts` | Model not found or column missing | Check FQCN in config; run migration |
| Draft created twice | `growthatlas_draft_id` not in `$fillable` | Add to `$fillable` on the model |
| Signature 401 | Secret mismatch | Match secret in `.env` and GrowthAtlas dashboard |

---

## Versioning

Follows [Semantic Versioning](https://semver.org/). The package targets Connector API **v1**. When GrowthAtlas releases API v2, a new major version will be released.

Full changelog: [CHANGELOG.md](CHANGELOG.md)

---

## Contributing

1. Fork and branch: `git checkout -b feat/my-feature`
2. Make changes and commit.
3. Open a pull request with a clear description.

---

## License

MIT. See [LICENSE](LICENSE).

---

## Support

- Docs: [growthatlas.io/connector-api](https://growthatlas.io/connector-api)
- Issues: [github.com/GrowthAtlas/laravel-connector/issues](https://github.com/GrowthAtlas/laravel-connector/issues)
- Email: support@growthatlas.io
