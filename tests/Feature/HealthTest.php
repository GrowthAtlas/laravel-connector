<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Support\Settings;
use GrowthAtlas\Connector\Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_returns_ok_status(): void
    {
        $this->getJson('/api/growthatlas/v1/health', $this->headers())
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'status'                  => 'ok',
                     'connector'               => 'laravel',
                     'growthatlas_api_version' => 'v1',
                 ],
             ]);
    }

    public function test_fails_when_api_key_is_not_configured(): void
    {
        config()->set('growthatlas-connector.api_key', null);
        // Clear in-memory settings cache from earlier tests.
        $ref = new \ReflectionClass(Settings::class);
        $prop = $ref->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $response = $this->getJson('/api/growthatlas/v1/health')
             ->assertStatus(503)
             ->assertJson([
                 'success' => false,
                 'data' => [
                     'status' => 'error',
                     'connector' => 'laravel',
                 ],
             ]);

        $this->assertStringContainsString('API key', (string) $response->json('data.message'));
    }
}
