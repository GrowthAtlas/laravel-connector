<?php

namespace GrowthAtlas\Connector\Tests\Feature;

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
}
