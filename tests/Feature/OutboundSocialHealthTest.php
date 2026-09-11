<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Support\OutboundSocialHealth;
use GrowthAtlas\Connector\Support\Settings;
use GrowthAtlas\Connector\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class OutboundSocialHealthTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        if (! Schema::hasTable('growthatlas_settings')) {
            Schema::create('growthatlas_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_instagram_alerts_come_from_inbound_health(): void
    {
        Cache::flush();
        Settings::set('outbound_inbound_token', 'ga_in_test');
        Settings::set('outbound_api_base', 'https://growthatlas.test');

        Http::fake([
            'https://growthatlas.test/api/inbound/v1/health' => Http::response([
                'data' => [
                    'ok' => true,
                    'warnings' => ['Instagram @shop expires in 3 days.'],
                    'instagram_alerts' => [
                        [
                            'code' => 'expiring',
                            'severity' => 'danger',
                            'message' => 'Instagram @shop expires in 3 days.',
                            'reconnect_url' => 'https://growthatlas.test/app/projects/10/social',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $alerts = OutboundSocialHealth::instagramAlerts();

        $this->assertCount(1, $alerts);
        $this->assertSame('expiring', $alerts[0]['code']);
        $this->assertSame('https://growthatlas.test/app/projects/10/social', $alerts[0]['reconnect_url']);
    }

    public function test_missing_token_returns_no_alerts(): void
    {
        Cache::flush();
        Settings::set('outbound_inbound_token', null);
        Http::fake();

        $this->assertSame([], OutboundSocialHealth::instagramAlerts());
        Http::assertNothingSent();
    }
}
