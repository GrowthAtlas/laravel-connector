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

    public function test_update_edits_existing_post_in_place(): void
    {
        $create = $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(7), $this->headers())
                       ->assertStatus(201);
        $externalId = $create->json('data.external_id');

        $updated = array_merge($this->payload(7), ['title' => 'Updated Title', 'body' => 'New body.']);

        $this->putJson("/api/growthatlas/v1/content-drafts/{$externalId}", $updated, $this->headers())
             ->assertStatus(200)
             ->assertJsonPath('data.updated', true)
             ->assertJsonPath('data.created', false);

        $post = \GrowthAtlas\Connector\Tests\Stubs\Post::where('growthatlas_draft_id', 7)->firstOrFail();
        $this->assertSame('Updated Title', $post->title);
        $this->assertSame('New body.', $post->body);
        $this->assertSame(1, \GrowthAtlas\Connector\Tests\Stubs\Post::where('growthatlas_draft_id', 7)->count());
    }

    public function test_update_falls_back_to_create_when_missing(): void
    {
        $this->putJson('/api/growthatlas/v1/content-drafts/999', $this->payload(55), $this->headers())
             ->assertStatus(201)
             ->assertJsonPath('data.created', true);

        $this->assertSame(1, \GrowthAtlas\Connector\Tests\Stubs\Post::where('growthatlas_draft_id', 55)->count());
    }

    public function test_create_url_includes_configured_url_prefix(): void
    {
        config()->set('growthatlas-connector.publishing.url_prefix', 'blog');

        $response = $this->postJson('/api/growthatlas/v1/content-drafts', $this->payload(90), $this->headers())
            ->assertStatus(201);

        $url = $response->json('data.url');
        $this->assertIsString($url);
        $this->assertMatchesRegularExpression('#/blog/test-article/?$#', parse_url($url, PHP_URL_PATH) ?? '');
    }
}
