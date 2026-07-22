<?php

namespace GrowthAtlas\Connector\Tests\Outbound;

use GrowthAtlas\Connector\Facades\GrowthAtlas;
use GrowthAtlas\Connector\Outbound\SocialClient;
use GrowthAtlas\Connector\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialClientTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('growthatlas-connector.outbound', [
            'api_base' => 'https://growthatlas.test',
            'inbound_token' => 'ga_in_test_token',
            'default_intake_mode' => null,
        ]);
    }

    public function test_push_post_sends_bearer_token_and_json_payload(): void
    {
        Http::fake([
            'https://growthatlas.test/api/inbound/v1/social-posts' => Http::response([
                'data' => [
                    'id' => 42,
                    'external_id' => 'campaign-1',
                    'status' => 'draft',
                    'format' => 'reel',
                ],
            ], 201),
        ]);

        $payload = [
            'external_id' => 'campaign-1',
            'format' => 'reel',
            'caption' => 'Hello world',
            'media' => [['url' => 'https://cdn.example.com/a.mp4']],
        ];

        $result = GrowthAtlas::social()->pushPost($payload);

        Http::assertSent(function ($request) use ($payload) {
            return $request->url() === 'https://growthatlas.test/api/inbound/v1/social-posts'
                && $request->hasHeader('Authorization', 'Bearer ga_in_test_token')
                && $request->hasHeader('Accept', 'application/json')
                && $request['external_id'] === $payload['external_id']
                && $request['format'] === 'reel'
                && $request['caption'] === 'Hello world'
                && $request['media'] === $payload['media'];
        });

        $this->assertSame(42, $result['data']['id']);
    }

    public function test_push_post_applies_default_intake_mode_when_omitted(): void
    {
        config()->set('growthatlas-connector.outbound.default_intake_mode', 'studio_draft');

        Http::fake([
            'https://growthatlas.test/api/inbound/v1/social-posts' => Http::response(['data' => ['id' => 1]], 201),
        ]);

        GrowthAtlas::social()->pushPost([
            'external_id' => 'x',
            'format' => 'feed_image',
            'media' => [['url' => 'https://cdn.example.com/a.jpg']],
        ]);

        Http::assertSent(fn ($request) => $request['intake_mode'] === 'studio_draft');
    }

    public function test_push_post_sends_idempotency_key_header_when_provided(): void
    {
        Http::fake([
            'https://growthatlas.test/api/inbound/v1/social-posts' => Http::response(['data' => ['id' => 1]], 201),
        ]);

        GrowthAtlas::social()->pushPost([
            'external_id' => 'x',
            'format' => 'feed_image',
            'media' => [['url' => 'https://cdn.example.com/a.jpg']],
        ], 'idem-123');

        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem-123'));
    }

    public function test_push_post_throws_when_inbound_token_missing(): void
    {
        config()->set('growthatlas-connector.outbound.inbound_token', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GROWTHATLAS_INBOUND_TOKEN');

        app(SocialClient::class)->pushPost([
            'external_id' => 'x',
            'format' => 'feed_image',
            'media' => [['url' => 'https://cdn.example.com/a.jpg']],
        ]);
    }

    public function test_get_post_fetches_status_by_id(): void
    {
        Http::fake([
            'https://growthatlas.test/api/inbound/v1/social-posts/42' => Http::response([
                'data' => [
                    'id' => 42,
                    'external_id' => 'campaign-1',
                    'status' => 'scheduled',
                ],
            ]),
        ]);

        $result = GrowthAtlas::social()->getPost(42);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://growthatlas.test/api/inbound/v1/social-posts/42'
                && $request->hasHeader('Authorization', 'Bearer ga_in_test_token');
        });

        $this->assertSame('scheduled', $result['data']['status']);
    }
}
