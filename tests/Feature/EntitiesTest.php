<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Tests\TestCase;

class EntitiesTest extends TestCase
{
    public function test_returns_empty_when_no_sources(): void
    {
        $this->getJson('/api/growthatlas/v1/entities', $this->headers())
             ->assertStatus(200)
             ->assertJson(['success' => true, 'data' => []])
             ->assertJsonPath('pagination.total', 0);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/growthatlas/v1/entities', $this->headers(false))
             ->assertStatus(401);
    }
}
