<?php

namespace GrowthAtlas\Connector\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class ConnectorController extends Controller
{
    // ── Health ──────────────────────────────────────────────────────────────

    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'connector' => 'laravel',
                'connector_version' => '1.0.0',
                'platform' => 'laravel',
                'platform_version' => App::version(),
                'php_version' => PHP_VERSION,
                'growthatlas_api_version' => 'v1',
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

        // Idempotency check — use the column directly, regardless of $fillable/$guarded
        // (models using $guarded = [] have an empty $fillable, so in_array check is wrong).
        if ($draftId > 0) {
            $existing = $modelClass::where($idColumn, $draftId)->first();
            if ($existing) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'external_id' => (string) $existing->getKey(),
                        'url' => method_exists($existing, 'getUrl') ? $existing->getUrl() : url($existing->slug ?? ''),
                        'status' => $existing->{$config['status_column'] ?? 'status'} ?? 'draft',
                        'created' => false,
                    ],
                ]);
            }
        }

        // Map fields
        $fieldMap = $config['fields'] ?? [];
        $statusMap = $config['status_map'] ?? ['draft' => 'draft', 'published' => 'published'];
        $statusColumn = $config['status_column'] ?? 'status';

        $modelData = [];
        foreach ($fieldMap as $gaField => $modelField) {
            if (isset($data[$gaField])) {
                $modelData[$modelField] = $data[$gaField];
            }
        }

        $requestedStatus = $data['publish_status'] ?? 'draft';
        $modelData[$statusColumn] = $statusMap[$requestedStatus] ?? 'draft';

        if ($draftId > 0) {
            $modelData[$idColumn] = $draftId;
        }

        $record = $modelClass::create($modelData);

        return response()->json([
            'success' => true,
            'data' => [
                'external_id' => (string) $record->getKey(),
                'url' => method_exists($record, 'getUrl') ? $record->getUrl() : url($record->slug ?? ''),
                'status' => $record->{$statusColumn} ?? 'draft',
                'created' => true,
            ],
        ], 201);
    }
}
