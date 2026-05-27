<?php

namespace GrowthAtlas\Connector\Tests\Feature;

use GrowthAtlas\Connector\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentDraftsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(int $id = 1): array
    {
        return [
            'growthatlas_draft_id' => $id,
            'title'                => 'Test Article',
            'slug'                 => 'test-article',
            'body'                 => 'Hello world.',
            'publish_status'       => 'draft',
            'source'               => 'growthatlas',
        ];
    }

    public function test_draft_is_created(): void
    {
        $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(), $this->headers())
             ->assertStatus(201)
             ->assertJsonPath('data.created', true)
             ->assertJsonPath('success', true);
    }

    public function test_duplicate_draft_is_idempotent_with_guarded_model(): void
    {
        // First call — creates
        $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(42), $this->headers());

        // Second call with same draft_id — must NOT create a duplicate
        $response = $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(42), $this->headers());

        $response->assertStatus(200)
                 ->assertJsonPath('data.created', false);

        // Only one row in the database
        $this->assertSame(1, \GrowthAtlas\Connector\Tests\Stubs\Post::where('growthatlas_draft_id', 42)->count());
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(), $this->headers(false))
             ->assertStatus(401);
    }
}
