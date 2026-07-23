<?php

namespace GrowthAtlas\Connector\Support;

use GrowthAtlas\Connector\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Central accessor for connector settings.
 *
 * Resolution order for every key:
 *   1. Value stored in the growthatlas_settings table (managed from the admin UI)
 *   2. The matching config/.env value (backwards compatible default)
 *
 * Values are cached in-memory for the lifetime of the request. Writing a value
 * clears that cache so subsequent reads see the new value immediately.
 */
class Settings
{
    /** Managed keys and the config key they fall back to. */
    public const KEYS = [
        'api_key'                => 'growthatlas-connector.api_key',
        'signing_secret'         => 'growthatlas-connector.signing_secret',
        'log_inbound'            => 'growthatlas-connector.log_inbound',
        'default_publish_status' => 'growthatlas-connector.publishing.default_publish_status',
        'outbound_api_base'      => 'growthatlas-connector.outbound.api_base',
        'outbound_inbound_token' => 'growthatlas-connector.outbound.inbound_token',
    ];

    /** @var array<string, string|null>|null */
    protected static ?array $cache = null;

    public static function apiKey(): ?string
    {
        $value = static::get('api_key');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public static function signingSecret(): ?string
    {
        $value = static::get('signing_secret');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public static function outboundApiBase(): string
    {
        $value = static::get('outbound_api_base', 'https://growthatlas.io');

        $base = is_string($value) && $value !== '' ? $value : 'https://growthatlas.io';

        return rtrim($base, '/');
    }

    public static function outboundInboundToken(): ?string
    {
        $value = static::get('outbound_inbound_token');

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    public static function loggingEnabled(): bool
    {
        return filter_var(static::get('log_inbound'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a setting: DB value first, then config/.env fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = static::stored();

        if (array_key_exists($key, $stored) && $stored[$key] !== null) {
            return $stored[$key];
        }

        $configKey = static::KEYS[$key] ?? null;

        return $configKey ? config($configKey, $default) : $default;
    }

    /**
     * Persist a setting to the database. Null/empty removes the override so the
     * config/.env fallback applies again.
     */
    public static function set(string $key, mixed $value): void
    {
        if (! static::tableExists()) {
            return;
        }

        $value = ($value === null || $value === '') ? null : (string) $value;

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        static::$cache = null;
    }

    /**
     * True when the value came from the DB rather than the .env fallback.
     */
    public static function isManaged(string $key): bool
    {
        $stored = static::stored();

        return array_key_exists($key, $stored) && $stored[$key] !== null;
    }

    /**
     * @return array<string, string|null>
     */
    protected static function stored(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        if (! static::tableExists()) {
            return static::$cache = [];
        }

        try {
            return static::$cache = Setting::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            return static::$cache = [];
        }
    }

    protected static function tableExists(): bool
    {
        try {
            return Schema::hasTable('growthatlas_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
