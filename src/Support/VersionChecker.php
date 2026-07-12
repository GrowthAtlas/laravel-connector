<?php

namespace GrowthAtlas\Connector\Support;

use GrowthAtlas\Connector\Http\Controllers\ConnectorController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Compares the installed connector version to the latest Packagist release.
 */
class VersionChecker
{
    public const PACKAGIST_URL = 'https://repo.packagist.org/p2/growthatlas/laravel-connector.json';

    public const CACHE_KEY = 'growthatlas_connector_latest_version';

    /** Cache Packagist lookups for 12 hours. */
    public const CACHE_TTL_SECONDS = 43200;

    public const RELEASES_URL = 'https://github.com/GrowthAtlas/laravel-connector/releases';

    /**
     * @return array{
     *     current: string,
     *     latest: string|null,
     *     update_available: bool,
     *     checked: bool,
     *     releases_url: string
     * }
     */
    public static function status(): array
    {
        $current = ConnectorController::CONNECTOR_VERSION;
        $latest = static::latestVersion();

        return [
            'current' => $current,
            'latest' => $latest,
            'update_available' => $latest !== null && version_compare($latest, $current, '>'),
            'checked' => $latest !== null,
            'releases_url' => self::RELEASES_URL,
        ];
    }

    public static function latestVersion(): ?string
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
                return static::fetchLatestFromPackagist();
            });
        } catch (Throwable) {
            return static::fetchLatestFromPackagist();
        }
    }

    protected static function fetchLatestFromPackagist(): ?string
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'GrowthAtlas-Laravel-Connector/'.ConnectorController::CONNECTOR_VERSION])
                ->get(self::PACKAGIST_URL);

            if (! $response->successful()) {
                return null;
            }

            $packages = $response->json('packages.growthatlas/laravel-connector') ?? [];
            $stable = [];

            foreach ($packages as $pkg) {
                $version = (string) ($pkg['version'] ?? '');
                if ($version === '' || str_starts_with($version, 'dev-')) {
                    continue;
                }
                // Skip pre-releases (alpha/beta/RC)
                if (preg_match('/(?:alpha|beta|rc)/i', $version)) {
                    continue;
                }
                $stable[] = ltrim($version, 'v');
            }

            if ($stable === []) {
                return null;
            }

            usort($stable, static fn (string $a, string $b): int => version_compare($b, $a));

            return $stable[0];
        } catch (Throwable) {
            return null;
        }
    }
}
