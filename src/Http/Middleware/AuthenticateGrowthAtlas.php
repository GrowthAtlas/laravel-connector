<?php

namespace GrowthAtlas\Connector\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateGrowthAtlas
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('growthatlas-connector.api_key');

        if (empty($configuredKey)) {
            return response()->json(['success' => false, 'message' => 'Connector API key not configured.'], 503);
        }

        $auth = $request->header('Authorization', '');
        if (! preg_match('/^Bearer (.+)$/i', $auth, $m)) {
            return response()->json(['success' => false, 'message' => 'Missing Bearer token.'], 401);
        }

        if (! hash_equals($configuredKey, $m[1])) {
            return response()->json(['success' => false, 'message' => 'Invalid API key.'], 401);
        }

        $secret = config('growthatlas-connector.signing_secret');
        if ($secret) {
            $signature = $request->header('X-GrowthAtlas-Signature', '');
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $signature)) {
                return response()->json(['success' => false, 'message' => 'Signature verification failed.'], 401);
            }
        }

        return $next($request);
    }
}
