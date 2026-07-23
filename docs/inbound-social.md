# Inbound Social — send content to GrowthAtlas

## Who this is for

Custom CMS, ML pipelines, or any app that generates social media (image/video + caption)
and wants GrowthAtlas Social Hub to approve/schedule/publish to Instagram.

## Prerequisites

1. GrowthAtlas project with Social Hub + connected Instagram profile(s)
2. Integration (Laravel / Custom API) with **Inbound Social** enabled
3. Inbound token (`ga_in_…`) from Integration → Inbound Social → Generate
4. Paste the token on your site’s Filament **GrowthAtlas** page (**Outbound token**), or set `GROWTHATLAS_INBOUND_TOKEN` as a fallback

## Base URL

`https://growthatlas.io/api/inbound/v1`

## Authentication

```
Authorization: Bearer ga_in_…
```

Optional (accepted and forwarded by the Laravel connector client; reserved for a future server-side cache):

```
Idempotency-Key: <unique-string>
```

Server idempotency is keyed on `external_id` per Integration. Re-sending the same
`external_id` returns the existing post without creating duplicates.

The inbound token is separate from your site’s connector API key (`ga_live_…`). GrowthAtlas
shows the inbound token once at generation/rotation — paste it into the connector Filament page
(**Outbound token**) or your secrets manager / `.env` fallback.

## Endpoints

| Method | Path | Content-Type |
|--------|------|--------------|
| POST | `/social-posts` | `application/json` |
| POST | `/social-posts/multipart` | `multipart/form-data` |
| GET | `/social-posts/{id}` | — |

Rate limit: **60 requests per minute** per token (HTTP `429` when exceeded).

## Parameter reference

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `external_id` | string | yes | Your stable id (idempotent per Integration). Max 191 characters. |
| `format` | string | yes | `feed_image` \| `feed_video` \| `reel` \| `carousel` \| `story` |
| `caption` | string | no | Post caption. Max 2200 characters. |
| `hashtags` | string[] | no | Hashtag strings without `#`. Each max 100 characters. |
| `scheduled_at` | ISO-8601 | no | Scheduling hint. Honored when intake mode allows; otherwise ignored. |
| `social_profile_ids` | int[] | no | GrowthAtlas social profile IDs to publish to. Overrides Integration defaults. |
| `intake_mode` | string | no | `studio_draft` \| `autopilot_queue` \| `publish_now`. Defaults to Integration setting. |
| `media` | array | yes | 1–20 items. See [Media](#media). |

### Social profile IDs

Find profile IDs in GrowthAtlas under **Social → Accounts** (each connected Instagram profile
has a numeric ID). You can also set default profile IDs on the Integration under
**Inbound Social → Default social profiles**; omit `social_profile_ids` in the payload to use
those defaults. If neither the payload nor the Integration provides profiles, the API returns
`422` with `social_profile_ids: ["destinations_required"]`.

## Media

### Limits (from GrowthAtlas `config/social.php`)

| Limit | Value |
|-------|-------|
| Max file size (multipart upload) | **300 MB** (`SOCIAL_MEDIA_MAX_UPLOAD_MB`, default 300) |
| Max download size (URL mode) | **300 MB** (314,572,800 bytes; override with `SOCIAL_INBOUND_MAX_DOWNLOAD_BYTES`) |
| URL download timeout | **30 seconds** (`SOCIAL_INBOUND_DOWNLOAD_TIMEOUT_SECONDS`) |
| Max items per post | **20** |
| Allowed MIME types | `image/jpeg`, `image/png`, `image/webp`, `image/gif`, `video/mp4`, `video/quicktime` |

Detected MIME type (not client-declared type alone) must match the allow-list. Video duration
and Instagram format rules are enforced during media processing and publishing.

### URL mode (JSON)

```json
"media": [
  { "url": "https://cdn.example.com/a.mp4", "mime_type": "video/mp4" }
]
```

- **HTTPS only** — `http://` URLs are rejected.
- URLs must be **publicly reachable** from GrowthAtlas (no `localhost`, private IPs, or
  link-local addresses).
- **Redirects are not followed** — the URL must return `200` with the file body directly.
- `mime_type` is optional but recommended; if provided, it must match the detected type.

### Multipart

Send the same logical fields as form fields; attach files as ordered parts:

- `media[0]` = first file
- `media[1]` = second file (carousel order)
- …

Mixed URL + file in one request is not supported — pick JSON or multipart per request.

## Intake modes

| Mode | Behavior |
|------|----------|
| `studio_draft` | Creates a `SocialPost` in **draft** with media and destinations. Finish in Social Studio. |
| `autopilot_queue` | Creates a complete post and schedules it on the Integration’s linked Social Autopilot campaign cadence (requires a linked campaign in Integration settings). |
| `publish_now` | Submits through the project’s approval policy, then schedules/publishes. **Never bypasses** required approvals. |

Configure the Integration default under **Inbound Social → Default intake mode**. Override per
request with `intake_mode`.

## Idempotency and updates

- Re-sending the same `external_id` for the same Integration returns the existing post (no
  duplicate media). This is the server’s idempotency key.
- The optional `Idempotency-Key` header may be sent by clients (including the Laravel
  connector) but is not used for server-side deduplication in v1; it is reserved for a
  future optional cache layer.
- Updates (replace caption/media) are allowed only while status is `draft`.
- When status is not `draft`, the API returns the existing post unchanged (HTTP `200`) —
  safe for idempotent retries; no recomposition or media re-ingest.

## Responses

### Success (200)

```json
{
  "data": {
    "id": 12345,
    "external_id": "campaign-42-post-7",
    "status": "draft",
    "format": "reel",
    "scheduled_at": null,
    "studio_url": "https://growthatlas.io/app/projects/1/social/studio/12345/edit"
  }
}
```

| Field | Description |
|-------|-------------|
| `id` | GrowthAtlas `social_post_id` — use for `GET /social-posts/{id}` |
| `external_id` | Your id from the request |
| `status` | Post workflow status (`draft`, `awaiting_internal_approval`, `scheduled`, etc.) |
| `format` | Echo of requested format |
| `scheduled_at` | ISO-8601 when scheduled; `null` otherwise |
| `studio_url` | Deep link to edit in Social Studio |

### Errors

| HTTP | When | Example body |
|------|------|--------------|
| `401` | Missing, malformed, or invalid inbound token | `{"message":"Unauthorized."}` |
| `403` | Inbound Social disabled on Integration | `{"message":"Inbound social is not enabled for this integration."}` |
| `404` | Post id not found for this Integration | Laravel default 404 |
| `422` | Validation, SSRF, unsupported media, missing destinations/campaign | `{"message":"…","errors":{"field":["reason"]}}` |
| `429` | Rate limit exceeded | `{"message":"Too Many Attempts."}` |

Common `422` examples:

- `social_profile_ids`: `destinations_required`
- `url`: `Only HTTPS media URLs are allowed.`
- `url`: `The media URL resolves to a private network address.`
- `intake_mode`: `A linked Social Autopilot campaign is required for autopilot_queue intake.`

## Examples

### curl (JSON)

```bash
curl -X POST https://growthatlas.io/api/inbound/v1/social-posts \
  -H "Authorization: Bearer ga_in_your_token_here" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: campaign-42-post-7-v1" \
  -d '{
    "external_id": "campaign-42-post-7",
    "format": "reel",
    "caption": "Three tips for better engagement",
    "hashtags": ["tips", "social"],
    "social_profile_ids": [123],
    "intake_mode": "studio_draft",
    "media": [
      {
        "url": "https://cdn.example.com/reel.mp4",
        "mime_type": "video/mp4"
      }
    ]
  }'
```

### curl (multipart)

```bash
curl -X POST https://growthatlas.io/api/inbound/v1/social-posts/multipart \
  -H "Authorization: Bearer ga_in_your_token_here" \
  -H "Accept: application/json" \
  -F 'external_id=campaign-42-post-7' \
  -F 'format=feed_image' \
  -F 'caption=Generated by our ML pipeline' \
  -F 'social_profile_ids[]=123' \
  -F 'intake_mode=studio_draft' \
  -F 'media[0]=@/path/to/image.jpg;type=image/jpeg'
```

For carousels, add `-F 'media[1]=@/path/to/slide-2.jpg;type=image/jpeg'`, etc.

### PHP (Laravel connector)

```php
use GrowthAtlas\Connector\Facades\GrowthAtlas;

$response = GrowthAtlas::social()->pushPost([
    'external_id' => 'campaign-42-post-7',
    'format' => 'reel',
    'caption' => 'Three tips for better engagement',
    'hashtags' => ['tips', 'social'],
    'social_profile_ids' => [123],
    'intake_mode' => 'studio_draft',
    'media' => [
        ['url' => 'https://cdn.example.com/reel.mp4', 'mime_type' => 'video/mp4'],
    ],
]);

$postId = $response['data']['id'];
$studioUrl = $response['data']['studio_url'];
```

Multipart from Laravel:

```php
$response = GrowthAtlas::social()->pushPostMultipart(
    [
        'external_id' => 'campaign-42-post-7',
        'format' => 'feed_image',
        'caption' => 'Generated locally',
        'social_profile_ids' => [123],
    ],
  files: [storage_path('app/generated/frame.jpg')],
);
```

Requires outbound credentials on the Filament **GrowthAtlas Connector** page
(**Outbound token** + optional **API base URL**). `.env` fallbacks:

```env
GROWTHATLAS_API_BASE=https://growthatlas.io
GROWTHATLAS_INBOUND_TOKEN=ga_in_your_token_here
```

Intake mode defaults come from the GrowthAtlas Integration setting — not from connector `.env`.
Override per request with `intake_mode` in the payload when needed.

### Generic HTTP (Python requests)

For custom ML pipelines or non-Laravel generators:

```python
import requests

payload = {
    "external_id": "ml-run-2026-07-23-post-1",
    "format": "feed_image",
    "caption": "AI-generated caption",
    "hashtags": ["ai", "automation"],
    "social_profile_ids": [123],
    "intake_mode": "studio_draft",
    "media": [
        {"url": "https://cdn.example.com/output.jpg", "mime_type": "image/jpeg"}
    ],
}

response = requests.post(
    "https://growthatlas.io/api/inbound/v1/social-posts",
    headers={
        "Authorization": "Bearer ga_in_your_token_here",
        "Content-Type": "application/json",
        "Idempotency-Key": "ml-run-2026-07-23-post-1",
    },
    json=payload,
    timeout=60,
)
response.raise_for_status()
data = response.json()["data"]
print(data["id"], data["studio_url"])
```

## Related docs

- [GrowthAtlas Connector API v1](https://growthatlas.io/connector-api) — platform-agnostic
  inbound social section (same field names)
- [Laravel connector README](../README.md) — package install and outbound client setup
