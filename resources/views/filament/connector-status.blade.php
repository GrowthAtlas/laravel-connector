<x-filament-panels::page>

    {{-- ── Status overview ──────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Connector status</x-slot>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">

            {{-- API Key --}}
            <div class="flex flex-col gap-2">
                @if($apiKeyConfigured)
                    <x-filament::badge color="success" icon="heroicon-m-check-circle" size="lg">
                        API Key configured
                    </x-filament::badge>
                @else
                    <x-filament::badge color="danger" icon="heroicon-m-x-circle" size="lg">
                        API Key missing
                    </x-filament::badge>
                @endif
                <p class="text-sm font-mono text-gray-500 dark:text-gray-400 break-all">
                    {{ $apiKeyMasked ?? 'Set GROWTHATLAS_API_KEY in .env' }}
                </p>
            </div>

            {{-- Signing Secret --}}
            <div class="flex flex-col gap-2">
                @if($signingConfigured)
                    <x-filament::badge color="success" icon="heroicon-m-shield-check" size="lg">
                        HMAC signing active
                    </x-filament::badge>
                @else
                    <x-filament::badge color="gray" icon="heroicon-m-shield-exclamation" size="lg">
                        Signing secret optional
                    </x-filament::badge>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $signingConfigured
                        ? 'HMAC-SHA256 signature verification is active.'
                        : 'Set GROWTHATLAS_SIGNING_SECRET in .env to enable.' }}
                </p>
            </div>

            {{-- Last inbound request --}}
            <div class="flex flex-col gap-2">
                @if(!$logEnabled)
                    <x-filament::badge color="gray" icon="heroicon-m-eye-slash" size="lg">
                        Logging disabled
                    </x-filament::badge>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Set <code class="font-mono">GROWTHATLAS_LOG_INBOUND=true</code> to track requests.
                    </p>
                @elseif($lastInbound)
                    <x-filament::badge color="info" icon="heroicon-m-arrow-down-tray" size="lg">
                        Last request {{ $lastInbound->diffForHumans() }}
                    </x-filament::badge>
                @else
                    <x-filament::badge color="gray" icon="heroicon-m-clock" size="lg">
                        No requests yet
                    </x-filament::badge>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No inbound requests have been logged yet.
                    </p>
                @endif
            </div>

        </div>
    </x-filament::section>

    {{-- ── Health endpoint ──────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">Health endpoint</x-slot>
        <x-slot name="description">Use this URL to test connectivity from the GrowthAtlas dashboard.</x-slot>

        <p class="text-sm font-mono break-all text-primary-600 dark:text-primary-400">
            {{ $healthUrl }}
        </p>
    </x-filament::section>

    {{-- ── Recent inbound requests ──────────────────────────────────────── --}}
    @if($logEnabled)
        <x-filament::section>
            <x-slot name="heading">Recent requests</x-slot>
            <x-slot name="description">Last 20 inbound requests from GrowthAtlas.</x-slot>

            @if($recentRequests->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No requests logged yet. Make a request to any connector endpoint to see it here.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200 dark:divide-white/10">
                        <thead>
                            <tr class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
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
                                        <x-filament::badge :color="$req->status_code >= 400 ? 'danger' : 'success'">
                                            {{ $req->status_code }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if(is_null($req->signature_valid))
                                            <span class="text-gray-400">—</span>
                                        @elseif($req->signature_valid)
                                            <x-filament::badge color="success">Valid</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger">Invalid</x-filament::badge>
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
