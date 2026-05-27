<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_missing_bearer_returns_401(): void
    {
        $this->getJson('/api/growthatlas/v1/site-profile', ['Accept' => 'application/json'])
             ->assertStatus(401);
    }

    public function test_wrong_key_returns_401(): void
    {
        $this->getJson('/api/growthatlas/v1/site-profile', [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer wrong-key',
        ])->assertStatus(401);
    }

    public function test_correct_key_passes(): void
    {
        $this->getJson('/api/growthatlas/v1/site-profile', $this->headers())
             ->assertStatus(200)
             ->assertJson(['success' => true]);
    }

    public function test_health_endpoint_is_accessible_without_auth(): void
    {
        // /health strips auth middleware intentionally — it's used by GrowthAtlas
        // to test if the site is reachable before a key is entered.
        $this->getJson('/api/growthatlas/v1/health', ['Accept' => 'application/json'])
             ->assertStatus(200);
    }
}
