{{--
  Layout CSS is self-contained: host apps do not compile this package's Tailwind
  classes, so Filament components alone are not enough for grids/tables/modals.
--}}
<x-filament-panels::page>
<style>
    .ga-cs { --ga-border: rgba(255,255,255,.08); --ga-muted: rgba(156,163,175,1); }
    /* Space stacked Filament sections — host Tailwind space-y-* is not available */
    .ga-cs > * + * { margin-top: 1.25rem; }
    .ga-cs__update {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: .85rem 1rem;
        border-radius: .75rem;
        border: 1px solid rgba(245, 158, 11, .45);
        background: rgba(245, 158, 11, .12);
        color: inherit;
    }
    .ga-cs__update-title { margin: 0; font-size: .9rem; font-weight: 600; }
    .ga-cs__update-body { margin: .25rem 0 0; font-size: .8rem; opacity: .9; }
    .ga-cs__update-meta { margin: .75rem 0 0; font-size: .75rem; opacity: .7; }
    .ga-cs__update-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .4rem .75rem;
        border-radius: .5rem;
        font-size: .8rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        background: rgba(245, 158, 11, .25);
        color: inherit;
        border: 1px solid rgba(245, 158, 11, .5);
    }
    .ga-cs__update-btn:hover { background: rgba(245, 158, 11, .35); }


    .ga-cs__cards {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .ga-cs__cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .ga-cs__card-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .ga-cs__card-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }
    .ga-cs__icon {
        display: flex;
        height: 2.25rem;
        width: 2.25rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
    }
    .ga-cs__icon--ok { background: rgba(34,197,94,.2); color: rgb(74,222,128); }
    .ga-cs__icon--bad { background: rgba(239,68,68,.2); color: rgb(248,113,113); }
    .ga-cs__icon--muted { background: rgba(255,255,255,.08); color: rgb(156,163,175); }
    .ga-cs__icon--info { background: rgba(59,130,246,.2); color: rgb(96,165,250); }
    .ga-cs__title { margin: 0; font-size: .875rem; font-weight: 600; }
    .ga-cs__sub { margin: .125rem 0 0; font-size: .75rem; color: var(--ga-muted); }
    .ga-cs__meta { margin: .75rem 0 0; font-size: .75rem; color: var(--ga-muted); word-break: break-all; }
    .ga-cs__meta--mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .ga-cs__endpoint {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }
    @media (min-width: 640px) {
        .ga-cs__endpoint {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }
    .ga-cs__endpoint-code {
        display: block;
        flex: 1;
        min-width: 0;
        padding: .5rem .75rem;
        border-radius: .5rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .8125rem;
        word-break: break-all;
        background: rgba(255,255,255,.04);
        color: rgb(96,165,250);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
    }
    .ga-cs__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: 2rem 1rem;
        text-align: center;
        color: var(--ga-muted);
        font-size: .875rem;
    }
    .ga-cs__table-wrap { overflow-x: auto; margin: 0 -.25rem; }
    .ga-cs__table {
        width: 100%;
        border-collapse: collapse;
        font-size: .875rem;
    }
    .ga-cs__table th {
        padding: .5rem .75rem;
        text-align: left;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--ga-muted);
        border-bottom: 1px solid color-mix(in srgb, currentColor 15%, transparent);
        white-space: nowrap;
    }
    .ga-cs__table th.ga-cs__th-right,
    .ga-cs__table td.ga-cs__td-right { text-align: right; }
    .ga-cs__table td {
        padding: .65rem .75rem;
        vertical-align: middle;
        border-bottom: 1px solid color-mix(in srgb, currentColor 12%, transparent);
    }
    .ga-cs__table tr:last-child td { border-bottom: 0; }
    .ga-cs__table tr:hover td { background: rgba(255,255,255,.03); }
    .ga-cs__title-cell { font-weight: 500; }
    .ga-cs__links {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .ga-cs__mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
</style>


    <div class="ga-cs">

        @if(! empty($versionStatus['update_available']))
            <div class="ga-cs__update" role="status">
                <div>
                    <p class="ga-cs__update-title">Update available</p>
                    <p class="ga-cs__update-body">
                        You are on <strong>v{{ $versionStatus['current'] }}</strong>.
                        Latest release is <strong>v{{ $versionStatus['latest'] }}</strong>.
                        Update the <code>growthatlas/laravel-connector</code> package on this site.
                    </p>
                </div>
                <a
                    class="ga-cs__update-btn"
                    href="{{ $versionStatus['releases_url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >View release</a>
            </div>
        @endif

        <p class="ga-cs__meta">Connector version v{{ $versionStatus['current'] ?? '—' }}@if(! empty($versionStatus['checked']) && empty($versionStatus['update_available'])) · up to date@endif</p>


        {{-- ── Status overview ──────────────────────────────────────────── --}}
        <div class="ga-cs__cards">

            <x-filament::section compact>
                <div class="ga-cs__card-row">
                    <div class="ga-cs__card-left">
                        <span @class([
                            'ga-cs__icon',
                            'ga-cs__icon--ok' => $apiKeyConfigured,
                            'ga-cs__icon--bad' => ! $apiKeyConfigured,
                        ])>
                            <x-filament::icon icon="heroicon-o-key" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="ga-cs__title">API key</p>
                            <p class="ga-cs__sub">{{ $apiKeyConfigured ? 'Configured' : 'Not set' }}</p>
                        </div>
                    </div>
                    <x-filament::badge :color="$apiKeyConfigured ? 'success' : 'danger'" size="sm">
                        {{ $apiKeyConfigured ? 'Ready' : 'Missing' }}
                    </x-filament::badge>
                </div>
                <p class="ga-cs__meta ga-cs__meta--mono">
                    {{ $apiKeyMasked ?? 'Use “Set API key” above to configure.' }}
                </p>
                @if($apiKeyConfigured)
                    <p class="ga-cs__meta">Source: {{ $apiKeyManaged ? 'managed here' : '.env fallback' }}</p>
                @endif
            </x-filament::section>

            <x-filament::section compact>
                <div class="ga-cs__card-row">
                    <div class="ga-cs__card-left">
                        <span @class([
                            'ga-cs__icon',
                            'ga-cs__icon--ok' => $signingConfigured,
                            'ga-cs__icon--muted' => ! $signingConfigured,
                        ])>
                            <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="ga-cs__title">HMAC signing</p>
                            <p class="ga-cs__sub">{{ $signingConfigured ? 'Active' : 'Optional' }}</p>
                        </div>
                    </div>
                    <x-filament::badge :color="$signingConfigured ? 'success' : 'gray'" size="sm">
                        {{ $signingConfigured ? 'On' : 'Off' }}
                    </x-filament::badge>
                </div>
                <p class="ga-cs__meta">
                    {{ $signingConfigured
                        ? 'Requests are verified with HMAC-SHA256.'
                        : 'Add a signing secret for an extra layer of security.' }}
                </p>
                @if($signingConfigured)
                    <p class="ga-cs__meta">Source: {{ $signingManaged ? 'managed here' : '.env fallback' }}</p>
                @endif
            </x-filament::section>

            <x-filament::section compact>
                <div class="ga-cs__card-row">
                    <div class="ga-cs__card-left">
                        <span @class([
                            'ga-cs__icon',
                            'ga-cs__icon--info' => $logEnabled,
                            'ga-cs__icon--muted' => ! $logEnabled,
                        ])>
                            <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="ga-cs__title">Request logging</p>
                            <p class="ga-cs__sub">{{ $logEnabled ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                    <x-filament::badge :color="$logEnabled ? 'info' : 'gray'" size="sm">
                        {{ $logEnabled ? 'On' : 'Off' }}
                    </x-filament::badge>
                </div>
                <p class="ga-cs__meta">
                    @if($logEnabled)
                        {{ $lastInbound ? 'Last request ' . $lastInbound->diffForHumans() : 'No requests logged yet.' }}
                    @else
                        Turn on to keep an audit trail of inbound requests.
                    @endif
                </p>
            </x-filament::section>
        </div>

        {{-- ── Connection endpoint ──────────────────────────────────────── --}}
        <x-filament::section icon="heroicon-o-link" icon-color="primary">
            <x-slot name="heading">Connection endpoint</x-slot>
            <x-slot name="description">Add this site to GrowthAtlas, then use “Test connection” above to verify it responds.</x-slot>

            <div
                x-data="{
                    copied: false,
                    copy() {
                        navigator.clipboard.writeText(@js($healthUrl));
                        this.copied = true;
                        setTimeout(() => this.copied = false, 1500);
                    }
                }"
                class="ga-cs__endpoint"
            >
                <code class="ga-cs__endpoint-code">{{ $healthUrl }}</code>
                <x-filament::button
                    size="sm"
                    color="gray"
                    icon="heroicon-m-clipboard"
                    x-on:click="copy()"
                    x-text="copied ? 'Copied!' : 'Copy URL'"
                >
                    Copy URL
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- ── Content received from GrowthAtlas ────────────────────────── --}}
        <x-filament::section icon="heroicon-o-document-text" icon-color="success">
            <x-slot name="heading">Content from GrowthAtlas</x-slot>
            <x-slot name="description">Articles published or updated on this site by GrowthAtlas.</x-slot>

            @if($receivedContent->isEmpty())
                <div class="ga-cs__empty">
                    <x-filament::icon icon="heroicon-o-inbox" class="h-10 w-10" style="color: rgba(156,163,175,.6)" />
                    <p>No content received yet. Publish a draft from GrowthAtlas to see it here.</p>
                </div>
            @else
                <div class="ga-cs__table-wrap">
                    <table class="ga-cs__table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>SEO</th>
                                <th>Updates</th>
                                <th>Last action</th>
                                <th class="ga-cs__th-right">Links</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receivedContent as $item)
                                <tr>
                                    <td class="ga-cs__title-cell">{{ $item->title ?: 'Untitled' }}</td>
                                    <td>
                                        <x-filament::badge
                                            :color="in_array($item->status, ['publish', 'published']) ? 'success' : 'gray'"
                                            size="sm"
                                        >
                                            {{ ucfirst($item->status ?: 'draft') }}
                                        </x-filament::badge>
                                    </td>
                                    <td>{{ $item->seo_score !== null ? $item->seo_score . '%' : '—' }}</td>
                                    <td>{{ $item->update_count > 0 ? $item->update_count . '×' : '—' }}</td>
                                    <td>{{ $item->last_action_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="ga-cs__td-right">
                                        <div class="ga-cs__links">
                                            @if($item->url)
                                                <x-filament::link
                                                    :href="$item->url"
                                                    target="_blank"
                                                    icon="heroicon-m-arrow-top-right-on-square"
                                                    size="sm"
                                                >
                                                    View
                                                </x-filament::link>
                                            @endif
                                            @if($item->growthatlas_url)
                                                <x-filament::link
                                                    :href="$item->growthatlas_url"
                                                    target="_blank"
                                                    icon="heroicon-m-arrow-top-right-on-square"
                                                    color="gray"
                                                    size="sm"
                                                >
                                                    GrowthAtlas
                                                </x-filament::link>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- ── Recent inbound requests ──────────────────────────────────── --}}
        @if($logEnabled)
            <x-filament::section
                icon="heroicon-o-clipboard-document-list"
                icon-color="info"
                collapsible
            >
                <x-slot name="heading">Recent requests</x-slot>
                <x-slot name="description">Last 20 inbound requests from GrowthAtlas.</x-slot>

                @if($recentRequests->isEmpty())
                    <p class="ga-cs__meta">No requests logged yet. Make a request to any connector endpoint to see it here.</p>
                @else
                    <div class="ga-cs__table-wrap">
                        <table class="ga-cs__table">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Status</th>
                                    <th>Sig</th>
                                    <th>IP</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRequests as $req)
                                    <tr>
                                        <td class="ga-cs__mono ga-cs__title-cell">{{ $req->endpoint }}</td>
                                        <td>
                                            <x-filament::badge :color="$req->status_code >= 400 ? 'danger' : 'success'" size="sm">
                                                {{ $req->status_code }}
                                            </x-filament::badge>
                                        </td>
                                        <td>
                                            @if(is_null($req->signature_valid))
                                                —
                                            @elseif($req->signature_valid)
                                                <x-filament::badge color="success" size="sm">Valid</x-filament::badge>
                                            @else
                                                <x-filament::badge color="danger" size="sm">Invalid</x-filament::badge>
                                            @endif
                                        </td>
                                        <td class="ga-cs__mono">{{ $req->ip ?? '—' }}</td>
                                        <td>{{ $req->created_at?->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
