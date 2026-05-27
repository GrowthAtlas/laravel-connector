<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- API Key --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex items-center gap-2 mb-2">
                    @if($apiKeyConfigured)
                        <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                        <span class="text-sm font-semibold text-green-700 dark:text-green-400">API Key configured</span>
                    @else
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">API Key missing</span>
                    @endif
                </div>
                <p class="text-xs font-mono text-gray-400">
                    {{ $apiKeyMasked ?? 'Set GROWTHATLAS_API_KEY in .env' }}
                </p>
            </div>

            {{-- Signing Secret --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex items-center gap-2 mb-2">
                    @if($signingConfigured)
                        <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                        <span class="text-sm font-semibold text-green-700 dark:text-green-400">Signing secret set</span>
                    @else
                        <span class="h-2.5 w-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span class="text-sm font-semibold text-gray-500">Signing secret optional</span>
                    @endif
                </div>
                <p class="text-xs text-gray-400">
                    {{ $signingConfigured ? 'HMAC-SHA256 verification active' : 'Set GROWTHATLAS_SIGNING_SECRET to enable' }}
                </p>
            </div>

            {{-- Last inbound --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-2.5 w-2.5 rounded-full {{ $lastInbound ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Last inbound request</span>
                </div>
                <p class="text-xs text-gray-400">
                    @if(!$logEnabled)
                        Logging disabled — set <code class="font-mono">log_inbound = true</code>
                    @elseif($lastInbound)
                        {{ $lastInbound->diffForHumans() }}
                    @else
                        No requests logged yet
                    @endif
                </p>
            </div>

        </div>

        {{-- Health URL --}}
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <span class="text-xs text-gray-500">Health endpoint:</span>
            <code class="text-xs font-mono text-blue-600 dark:text-blue-400 break-all">{{ $healthUrl }}</code>
        </div>

        {{-- Recent requests table --}}
        @if($logEnabled && $recentRequests->isNotEmpty())
            <div>
                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Recent requests (last 20)</h3>
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase tracking-wide">Endpoint</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase tracking-wide">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase tracking-wide">Sig</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase tracking-wide">IP</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase tracking-wide">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($recentRequests as $req)
                                <tr>
                                    <td class="px-3 py-2 font-mono">{{ $req->endpoint }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold
                                            {{ $req->status_code >= 400 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $req->status_code }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-400">
                                        @if(is_null($req->signature_valid)) <span>—</span>
                                        @elseif($req->signature_valid) <span class="text-green-600">✓</span>
                                        @else <span class="text-red-600">✗</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-gray-400">{{ $req->ip ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-400">{{ $req->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($logEnabled)
            <p class="text-sm text-gray-400">No inbound requests logged yet. Make a request to any connector endpoint to see it here.</p>
        @endif

    </div>
</x-filament-panels::page>
