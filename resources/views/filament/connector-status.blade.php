<x-filament-panels::page>

    {{-- ── Status overview ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- API Key --}}
        <x-filament::section compact>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span @class([
                        'flex h-9 w-9 items-center justify-center rounded-lg',
                        'bg-success-100 text-success-600 dark:bg-success-500/20 dark:text-success-400' => $apiKeyConfigured,
                        'bg-danger-100 text-danger-600 dark:bg-danger-500/20 dark:text-danger-400' => ! $apiKeyConfigured,
                    ])>
                        <x-filament::icon icon="heroicon-o-key" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">API key</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $apiKeyConfigured ? 'Configured' : 'Not set' }}
                        </p>
                    </div>
                </div>
                <x-filament::badge :color="$apiKeyConfigured ? 'success' : 'danger'" size="sm">
                    {{ $apiKeyConfigured ? 'Ready' : 'Missing' }}
                </x-filament::badge>
            </div>
            <p class="mt-3 break-all font-mono text-xs text-gray-500 dark:text-gray-400">
                {{ $apiKeyMasked ?? 'Use “Set API key” above to configure.' }}
            </p>
            @if($apiKeyConfigured)
                <p class="mt-1 text-xs text-gray-400">
                    Source: {{ $apiKeyManaged ? 'managed here' : '.env fallback' }}
                </p>
            @endif
        </x-filament::section>

        {{-- Signing Secret --}}
        <x-filament::section compact>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span @class([
                        'flex h-9 w-9 items-center justify-center rounded-lg',
                        'bg-success-100 text-success-600 dark:bg-success-500/20 dark:text-success-400' => $signingConfigured,
                        'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => ! $signingConfigured,
                    ])>
                        <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">HMAC signing</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $signingConfigured ? 'Active' : 'Optional' }}
                        </p>
                    </div>
                </div>
                <x-filament::badge :color="$signingConfigured ? 'success' : 'gray'" size="sm">
                    {{ $signingConfigured ? 'On' : 'Off' }}
                </x-filament::badge>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ $signingConfigured
                    ? 'Requests are verified with HMAC-SHA256.'
                    : 'Add a signing secret for an extra layer of security.' }}
            </p>
            @if($signingConfigured)
                <p class="mt-1 text-xs text-gray-400">
                    Source: {{ $signingManaged ? 'managed here' : '.env fallback' }}
                </p>
            @endif
        </x-filament::section>

        {{-- Logging --}}
        <x-filament::section compact>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span @class([
                        'flex h-9 w-9 items-center justify-center rounded-lg',
                        'bg-info-100 text-info-600 dark:bg-info-500/20 dark:text-info-400' => $logEnabled,
                        'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => ! $logEnabled,
                    ])>
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">Request logging</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $logEnabled ? 'Enabled' : 'Disabled' }}
                        </p>
                    </div>
                </div>
                <x-filament::badge :color="$logEnabled ? 'info' : 'gray'" size="sm">
                    {{ $logEnabled ? 'On' : 'Off' }}
                </x-filament::badge>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                @if($logEnabled)
                    {{ $lastInbound ? 'Last request ' . $lastInbound->diffForHumans() : 'No requests logged yet.' }}
                @else
                    Turn on to keep an audit trail of inbound requests.
                @endif
            </p>
        </x-filament::section>
    </div>

    {{-- ── Connection endpoint (health URL + inline test hint) ───────────── --}}
    <x-filament::section
        icon="heroicon-o-link"
        icon-color="primary"
    >
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
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <code class="break-all rounded-lg bg-gray-50 px-3 py-2 font-mono text-sm text-primary-600 ring-1 ring-inset ring-gray-950/5 dark:bg-white/5 dark:text-primary-400 dark:ring-white/10">
                {{ $healthUrl }}
            </code>
            <x-filament::button
                size="sm"
                color="gray"
                icon="heroicon-m-clipboard"
                x-on:click="copy()"
                x-text="copied ? 'Copied!' : 'Copy URL'"
                class="shrink-0"
            >
                Copy URL
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- ── Content received from GrowthAtlas ─────────────────────────────── --}}
    <x-filament::section
        icon="heroicon-o-document-text"
        icon-color="success"
    >
        <x-slot name="heading">Content from GrowthAtlas</x-slot>
        <x-slot name="description">Articles published or updated on this site by GrowthAtlas.</x-slot>

        @if($receivedContent->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                <x-filament::icon icon="heroicon-o-inbox" class="h-10 w-10 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No content received yet. Publish a draft from GrowthAtlas to see it here.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="px-3 py-2 text-left">Title</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-left">SEO</th>
                            <th class="px-3 py-2 text-left">Updates</th>
                            <th class="px-3 py-2 text-left">Last action</th>
                            <th class="px-3 py-2 text-right">Links</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($receivedContent as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-3 py-2.5">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $item->title ?: 'Untitled' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <x-filament::badge
                                        :color="in_array($item->status, ['publish', 'published']) ? 'success' : 'gray'"
                                        size="sm"
                                    >
                                        {{ ucfirst($item->status ?: 'draft') }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">
                                    {{ $item->seo_score !== null ? $item->seo_score . '%' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">
                                    {{ $item->update_count > 0 ? $item->update_count . '×' : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-500 dark:text-gray-400">
                                    {{ $item->last_action_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-end gap-2">
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

    {{-- ── Recent inbound requests ──────────────────────────────────────── --}}
    @if($logEnabled)
        <x-filament::section
            icon="heroicon-o-clipboard-document-list"
            icon-color="info"
            collapsible
        >
            <x-slot name="heading">Recent requests</x-slot>
            <x-slot name="description">Last 20 inbound requests from GrowthAtlas.</x-slot>

            @if($recentRequests->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No requests logged yet. Make a request to any connector endpoint to see it here.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <th class="px-3 py-2 text-left">Endpoint</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Sig</th>
                                <th class="px-3 py-2 text-left">IP</th>
                                <th class="px-3 py-2 text-left">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($recentRequests as $req)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-gray-900 dark:text-white">
                                        {{ $req->endpoint }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <x-filament::badge :color="$req->status_code >= 400 ? 'danger' : 'success'" size="sm">
                                            {{ $req->status_code }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if(is_null($req->signature_valid))
                                            <span class="text-gray-400">—</span>
                                        @elseif($req->signature_valid)
                                            <x-filament::badge color="success" size="sm">Valid</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger" size="sm">Invalid</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400">
                                        {{ $req->ip ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                                        {{ $req->created_at?->diffForHumans() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif

</x-filament-panels::page>
