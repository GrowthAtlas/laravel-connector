<?php

namespace GrowthAtlas\Connector\Http\Controllers;

use GrowthAtlas\Connector\Models\ReceivedContent;
use GrowthAtlas\Connector\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConnectorController extends Controller
{
    public const CONNECTOR_VERSION = '1.7.3';

    // ── Health ──────────────────────────────────────────────────────────────

    public function health(): JsonResponse
    {
        $apiKey = Settings::apiKey();

        if ($apiKey === null || $apiKey === '') {
            return response()->json([
                'success' => false,
                'data' => [
                    'status' => 'error',
                    'message' => 'API key is not configured. Set it in the GrowthAtlas admin page or GROWTHATLAS_API_KEY.',
                    'connector' => 'laravel',
                    'connector_version' => self::CONNECTOR_VERSION,
                    'platform' => 'laravel',
                    'platform_version' => App::version(),
                    'php_version' => PHP_VERSION,
                    'growthatlas_api_version' => 'v1',
                    'supports_update' => true,
                ],
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'connector' => 'laravel',
                'connector_version' => self::CONNECTOR_VERSION,
                'platform' => 'laravel',
                'platform_version' => App::version(),
                'php_version' => PHP_VERSION,
                'growthatlas_api_version' => 'v1',
                'supports_update' => true,
            ],
        ]);
    }

    // ── Site Profile ─────────────────────────────────────────────────────────

    public function siteProfile(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'description' => null,
                'language' => config('app.locale', 'en'),
                'platform' => 'laravel',
                'timezone' => config('app.timezone', 'UTC'),
                'post_types' => ['post'],
                'taxonomies' => [],
            ],
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function pages(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(1, (int) $request->input('per_page', 100)));

        $config = config('growthatlas-connector.pages');
        $source = $config['source'] ?? 'eloquent';

        if ($source === 'eloquent') {
            $modelClass = $config['model'];
            if (! class_exists($modelClass)) {
                return response()->json(['success' => false, 'message' => "Model {$modelClass} not found."], 500);
            }

            $paginator = $modelClass::paginate($perPage, ['*'], 'page', $page);
            $urlColumn = $config['url_column'] ?? 'slug';

            $items = collect($paginator->items())->map(fn ($m) => [
                'url' => url($m->{$urlColumn} ?? ''),
                'title' => $m->title ?? $m->name ?? '',
                'meta_description' => $m->meta_description ?? '',
                'h1' => $m->title ?? $m->name ?? '',
            ])->values()->all();

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [],
            'pagination' => ['current_page' => 1, 'per_page' => $perPage, 'total' => 0, 'last_page' => 1],
        ]);
    }

    // ── Entities ──────────────────────────────────────────────────────────────

    public function entities(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(1, (int) $request->input('per_page', 100)));

        $entities = config('growthatlas-connector.entities', []);
        $allItems = [];

        foreach ($entities as $type => $modelClass) {
            if (! class_exists($modelClass)) continue;

            $records = $modelClass::take($perPage)->get();
            foreach ($records as $record) {
                $allItems[] = [
                    'id' => (string) $record->getKey(),
                    'type' => $type,
                    'name' => $record->name ?? $record->title ?? $record->getKey(),
                    'slug' => $record->slug ?? Str::slug($record->name ?? ''),
                    'description' => $record->description ?? null,
                    'url' => isset($record->slug) ? url($record->slug) : null,
                    'priority' => 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_slice($allItems, ($page - 1) * $perPage, $perPage),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($allItems),
                'last_page' => max(1, (int) ceil(count($allItems) / $perPage)),
            ],
        ]);
    }

    // ── Content Drafts ────────────────────────────────────────────────────────

    /**
     * Create a new post from a GrowthAtlas draft (idempotent).
     */
    public function createContentDraft(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $draftId = (int) ($data['growthatlas_draft_id'] ?? 0);

        $config = config('growthatlas-connector.publishing');
        $modelClass = $config['model'];

        if (! class_exists($modelClass)) {
            return response()->json(['success' => false, 'message' => "Publish model {$modelClass} not found."], 500);
        }

        $idColumn = $config['growthatlas_id_column'] ?? 'growthatlas_draft_id';
        $statusColumn = $config['status_column'] ?? 'status';

        // Idempotency check — use the column directly, regardless of $fillable/$guarded
        // (models using $guarded = [] have an empty $fillable, so in_array check is wrong).
        if ($draftId > 0) {
            $existing = $modelClass::where($idColumn, $draftId)->first();
            if ($existing) {
                $this->recordReceived($existing, $data, $config, updated: false);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'external_id' => (string) $existing->getKey(),
                        'url' => $this->recordUrl($existing),
                        'status' => $existing->{$statusColumn} ?? 'draft',
                        'created' => false,
                    ],
                ]);
            }
        }

        $modelData = $this->mapPayloadToModel($data, $config);
        if ($draftId > 0) {
            $modelData[$idColumn] = $draftId;
        }

        $record = $modelClass::create($modelData);

        $this->recordReceived($record, $data, $config, updated: false);

        return response()->json([
            'success' => true,
            'data' => [
                'external_id' => (string) $record->getKey(),
                'url' => $this->recordUrl($record),
                'status' => $record->{$statusColumn} ?? 'draft',
                'created' => true,
            ],
        ], 201);
    }

    /**
     * Update an existing post from a refreshed GrowthAtlas draft.
     *
     * Resolves the target post by the external id in the path, falling back to
     * the growthatlas_draft_id in the payload. If nothing matches we create it,
     * so a "send update" from GrowthAtlas never silently no-ops.
     */
    public function updateContentDraft(Request $request, string $externalId): JsonResponse
    {
        $data = $request->json()->all();
        $draftId = (int) ($data['growthatlas_draft_id'] ?? 0);

        $config = config('growthatlas-connector.publishing');
        $modelClass = $config['model'];

        if (! class_exists($modelClass)) {
            return response()->json(['success' => false, 'message' => "Publish model {$modelClass} not found."], 500);
        }

        $idColumn = $config['growthatlas_id_column'] ?? 'growthatlas_draft_id';
        $statusColumn = $config['status_column'] ?? 'status';

        $record = null;
        if ($externalId !== '' && $externalId !== '0') {
            $record = $modelClass::find($externalId);
        }
        if (! $record && $draftId > 0) {
            $record = $modelClass::where($idColumn, $draftId)->first();
        }

        // Nothing to update — fall back to create so the refresh still lands.
        if (! $record) {
            return $this->createContentDraft($request);
        }

        $modelData = $this->mapPayloadToModel($data, $config);
        // Never flip the id column on update.
        unset($modelData[$idColumn]);

        $record->fill($modelData);
        $record->save();

        $this->recordReceived($record, $data, $config, updated: true);

        return response()->json([
            'success' => true,
            'data' => [
                'external_id' => (string) $record->getKey(),
                'url' => $this->recordUrl($record),
                'status' => $record->{$statusColumn} ?? 'draft',
                'created' => false,
                'updated' => true,
            ],
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /**
     * Map a GrowthAtlas payload onto model columns using the configured field map.
     */
    protected function mapPayloadToModel(array $data, array $config): array
    {
        $fieldMap = $config['fields'] ?? [];
        $statusMap = $config['status_map'] ?? ['draft' => 'draft', 'published' => 'published'];
        $statusColumn = $config['status_column'] ?? 'status';

        $modelData = [];
        foreach ($fieldMap as $gaField => $modelField) {
            if (isset($data[$gaField])) {
                $modelData[$modelField] = $data[$gaField];
            }
        }

        $default = Settings::get('default_publish_status', $config['default_publish_status'] ?? 'draft');
        $requestedStatus = $data['publish_status'] ?? $default;
        $modelData[$statusColumn] = $statusMap[$requestedStatus] ?? 'draft';

        $publishedAtColumn = $config['published_at_column'] ?? null;
        if ($publishedAtColumn && $requestedStatus === 'published' && ! isset($modelData[$publishedAtColumn])) {
            $modelData[$publishedAtColumn] = now();
        }

        return $modelData;
    }

    protected function recordUrl($record): string
    {
        $prefix = trim((string) (config('growthatlas-connector.publishing.url_prefix') ?? ''), '/');

        // Explicit path prefix wins so sites under /blog (etc.) return the correct
        // public URL without requiring a getUrl() method on the model.
        if ($prefix !== '') {
            return $this->urlFromSlug($record->slug ?? null, $prefix);
        }

        if (method_exists($record, 'getUrl')) {
            return (string) $record->getUrl();
        }

        return $this->urlFromSlug($record->slug ?? null, '');
    }

    /**
     * Build an absolute URL from an optional path prefix and slug.
     */
    protected function urlFromSlug(?string $slug, string $prefix = ''): string
    {
        $slug = ltrim((string) $slug, '/');
        $prefix = trim($prefix, '/');

        if ($prefix !== '' && $slug !== '') {
            return url($prefix.'/'.$slug);
        }

        if ($prefix !== '') {
            return url($prefix);
        }

        return url($slug);
    }

    /**
     * Upsert the received-content audit row so the admin page can list and link
     * every article received from GrowthAtlas. Never throws.
     */
    protected function recordReceived($record, array $data, array $config, bool $updated): void
    {
        try {
            if (! Schema::hasTable('growthatlas_received_content')) {
                return;
            }

            $draftId = (int) ($data['growthatlas_draft_id'] ?? 0);
            $statusColumn = $config['status_column'] ?? 'status';

            $attributes = [
                'external_id'          => (string) $record->getKey(),
                'title'                => $data['title'] ?? ($record->title ?? null),
                'url'                  => $this->recordUrl($record),
                'growthatlas_url'      => $data['growthatlas_url'] ?? null,
                'growthatlas_brief_id' => isset($data['growthatlas_brief_id']) ? (int) $data['growthatlas_brief_id'] : null,
                'status'               => $record->{$statusColumn} ?? ($data['publish_status'] ?? null),
                'seo_score'            => isset($data['seo_score']) ? (int) $data['seo_score'] : null,
                'last_action_at'       => now(),
            ];

            $existing = $draftId > 0
                ? ReceivedContent::where('growthatlas_draft_id', $draftId)->first()
                : null;

            if ($existing) {
                if ($updated) {
                    $attributes['update_count'] = (int) $existing->update_count + 1;
                }
                $existing->fill($attributes)->save();

                return;
            }

            $attributes['growthatlas_draft_id'] = $draftId > 0 ? $draftId : null;
            $attributes['update_count'] = $updated ? 1 : 0;
            ReceivedContent::create($attributes);
        } catch (\Throwable) {
            // Auditing must never break publishing.
        }
    }
}
