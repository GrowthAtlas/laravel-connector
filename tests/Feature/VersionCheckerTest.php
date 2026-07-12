<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Support\VersionChecker;
use GrowthAtlas\Connector\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VersionCheckerTest extends TestCase
{
    public function test_detects_newer_packagist_version(): void
    {
        Cache::flush();

        Http::fake([
            VersionChecker::PACKAGIST_URL => Http::response([
                'packages' => [
                    'growthatlas/laravel-connector' => [
                        ['version' => '9.9.9'],
                        ['version' => '1.0.0'],
                        ['version' => 'dev-main'],
                    ],
                ],
            ], 200),
        ]);

        $status = VersionChecker::status();

        $this->assertSame('9.9.9', $status['latest']);
        $this->assertTrue($status['update_available']);
        $this->assertTrue($status['checked']);
    }

    public function test_no_update_when_current_is_latest(): void
    {
        Cache::flush();

        $current = \GrowthAtlas\Connector\Http\Controllers\ConnectorController::CONNECTOR_VERSION;

        Http::fake([
            VersionChecker::PACKAGIST_URL => Http::response([
                'packages' => [
                    'growthatlas/laravel-connector' => [
                        ['version' => $current],
                        ['version' => '0.0.1'],
                    ],
                ],
            ], 200),
        ]);

        $status = VersionChecker::status();

        $this->assertSame($current, $status['latest']);
        $this->assertFalse($status['update_available']);
    }
}
