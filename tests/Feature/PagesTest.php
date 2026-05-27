<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_returns_paginated_shape(): void
    {
        $this->getJson('/api/growthatlas/v1/pages', $this->headers())
             ->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'data',
                 'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
             ]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/growthatlas/v1/pages', $this->headers(false))
             ->assertStatus(401);
    }
}
