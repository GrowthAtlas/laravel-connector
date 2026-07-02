<?php

namespace GrowthAtlas\Connector\Http\Middleware;

use Closure;
use GrowthAtlas\Connector\Models\InboundRequest;
use GrowthAtlas\Connector\Support\Settings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Appends an audit-log row after each inbound GrowthAtlas request.
 * Only active when growthatlas-connector.log_inbound = true.
 * Never throws — logging errors must not affect the main response.
 */
class LogInboundRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Settings::loggingEnabled()) {
            return $response;
        }

        try {
            $segments  = array_values(array_filter(explode('/', $request->path())));
            $endpoint  = end($segments) ?: 'unknown';

            $secret    = Settings::signingSecret();
            $sigHeader = $request->header('X-GrowthAtlas-Signature');
            $sigValid  = null;
            if ($sigHeader && $secret) {
                $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
                $sigValid = hash_equals($expected, $sigHeader);
            }

            $payloadSummary = null;
            if ($request->isMethod('POST')) {
                $json = $request->json()->all();
                $payloadSummary = [
                    'draft_id' => $json['growthatlas_draft_id'] ?? null,
                    'title'    => isset($json['title']) ? substr($json['title'], 0, 80) : null,
                    'keys'     => array_keys($json),
                ];
            }

            InboundRequest::create([
                'endpoint'        => substr($endpoint, 0, 64),
                'status_code'     => $response->getStatusCode(),
                'signature_valid' => $sigValid,
                'payload_summary' => $payloadSummary,
                'ip'              => $request->ip(),
                'created_at'      => now(),
            ]);
        } catch (\Throwable) {
            // Swallow silently.
        }

        return $response;
    }
}
