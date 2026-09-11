<?php

namespace GrowthAtlas\Connector\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cached GrowthAtlas inbound health check so the Filament page can surface
 * Instagram reconnect / token-expiry warnings without an extra click.
 */
class OutboundSocialHealth
{
    public const CACHE_TTL_SECONDS = 300;

    /**
     * @return list<array<string, mixed>>
     */
    public static function instagramAlerts(): array
    {
        $data = static::payload();
        $alerts = $data['instagram_alerts'] ?? null;

        if (is_array($alerts) && $alerts !== []) {
            return array_values($alerts);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $token = Settings::outboundInboundToken();
        if ($token === null || $token === '') {
            return [];
        }

        $url = rtrim(Settings::outboundApiBase(), '/').'/api/inbound/v1/health';
        $cacheKey = 'growthatlas_outbound_social_health_'.sha1($url.'|'.$token);

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($url, $token) {
                $response = Http::timeout(8)
                    ->withToken($token)
                    ->acceptJson()
                    ->get($url);

                if (! $response->successful()) {
                    return [];
                }

                $data = $response->json('data') ?? $response->json();

                return is_array($data) ? $data : [];
            });
        } catch (Throwable) {
            return [];
        }
    }
}
