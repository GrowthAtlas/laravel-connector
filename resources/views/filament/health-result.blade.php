@php
    /** @var array $result */
    $ok = $result['ok'] ?? false;
    $data = $result['data'] ?? [];
@endphp

<div class="ga-hr-root">
<style>
    .ga-hr { font-size: .875rem; }
    .ga-hr__banner {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        border-radius: .75rem;
        margin-bottom: 1rem;
    }
    .ga-hr__banner--ok {
        background: rgba(34,197,94,.12);
        color: rgb(74,222,128);
    }
    .ga-hr__banner--bad {
        background: rgba(239,68,68,.12);
        color: rgb(248,113,113);
    }
    .ga-hr__banner-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }
    .ga-hr__banner-sub {
        margin: .15rem 0 0;
        font-size: .8125rem;
        opacity: .85;
    }
    .ga-hr__list {
        margin: 0;
        padding: 0;
        list-style: none;
        border: 1px solid color-mix(in srgb, currentColor 15%, transparent);
        border-radius: .75rem;
        overflow: hidden;
    }
    .ga-hr__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .65rem 1rem;
        border-bottom: 1px solid color-mix(in srgb, currentColor 10%, transparent);
    }
    .ga-hr__row:last-child { border-bottom: 0; }
    .ga-hr__label {
        color: rgb(156,163,175);
        flex-shrink: 0;
    }
    .ga-hr__value {
        font-weight: 500;
        text-align: right;
        word-break: break-all;
    }
    .ga-hr__value--mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .75rem;
        font-weight: 400;
        opacity: .85;
    }
</style>

<div class="ga-hr">
    <div @class([
        'ga-hr__banner',
        'ga-hr__banner--ok' => $ok,
        'ga-hr__banner--bad' => ! $ok,
    ])>
        <x-filament::icon
            :icon="$ok ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
            class="h-8 w-8 shrink-0"
        />
        <div>
            <p class="ga-hr__banner-title">
                {{ $ok ? 'Connector is reachable' : 'Connector is not reachable' }}
            </p>
            <p class="ga-hr__banner-sub">
                {{ $ok
                    ? 'GrowthAtlas can talk to this site.'
                    : ($result['error'] ?? 'The health endpoint could not be reached.') }}
            </p>
        </div>
    </div>

    <dl class="ga-hr__list">
        <div class="ga-hr__row">
            <dt class="ga-hr__label">Endpoint</dt>
            <dd class="ga-hr__value ga-hr__value--mono">{{ $result['url'] ?? '—' }}</dd>
        </div>
        <div class="ga-hr__row">
            <dt class="ga-hr__label">HTTP status</dt>
            <dd class="ga-hr__value">{{ $result['status'] ?? '—' }}</dd>
        </div>
        @if(is_array($data) && ! empty($data))
            @foreach([
                'connector' => 'Connector',
                'connector_version' => 'Connector version',
                'platform_version' => 'Platform',
                'php_version' => 'PHP',
                'message' => 'Message',
            ] as $key => $label)
                @if(! empty($data[$key]))
                    <div class="ga-hr__row">
                        <dt class="ga-hr__label">{{ $label }}</dt>
                        <dd class="ga-hr__value">{{ $data[$key] }}</dd>
                    </div>
                @endif
            @endforeach
        @endif
    </dl>
</div>
</div>
