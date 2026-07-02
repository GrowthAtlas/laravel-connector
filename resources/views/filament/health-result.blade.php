@php
    /** @var array $result */
    $ok = $result['ok'] ?? false;
    $data = $result['data'] ?? [];
@endphp

<div class="space-y-4">
    <div @class([
        'flex items-center gap-3 rounded-xl px-4 py-3',
        'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $ok,
        'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400' => ! $ok,
    ])>
        <x-filament::icon
            :icon="$ok ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
            class="h-8 w-8 shrink-0"
        />
        <div>
            <p class="text-base font-semibold">
                {{ $ok ? 'Connector is reachable' : 'Connector is not reachable' }}
            </p>
            <p class="text-sm opacity-80">
                {{ $ok
                    ? 'GrowthAtlas can talk to this site.'
                    : ($result['error'] ?? 'The health endpoint could not be reached.') }}
            </p>
        </div>
    </div>

    <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 text-sm dark:divide-white/5 dark:border-white/10">
        <div class="flex items-center justify-between gap-4 px-4 py-2.5">
            <dt class="text-gray-500 dark:text-gray-400">Endpoint</dt>
            <dd class="truncate font-mono text-xs text-gray-700 dark:text-gray-300">{{ $result['url'] ?? '—' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-2.5">
            <dt class="text-gray-500 dark:text-gray-400">HTTP status</dt>
            <dd class="font-medium text-gray-900 dark:text-white">{{ $result['status'] ?? '—' }}</dd>
        </div>
        @if(is_array($data) && ! empty($data))
            @foreach(['connector' => 'Connector', 'connector_version' => 'Connector version', 'platform_version' => 'Platform', 'php_version' => 'PHP'] as $key => $label)
                @if(! empty($data[$key]))
                    <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                        <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $data[$key] }}</dd>
                    </div>
                @endif
            @endforeach
        @endif
    </dl>
</div>
