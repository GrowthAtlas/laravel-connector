<?php

namespace GrowthAtlas\Connector\Console;

use GrowthAtlas\Connector\Outbound\SocialClient;
use Illuminate\Console\Command;
use Throwable;

class PushSocialPostCommand extends Command
{
    protected $signature = 'growthatlas:push-social-post
                            {--external-id= : Stable idempotent id for this post}
                            {--format= : feed_image, feed_video, reel, carousel, or story}
                            {--caption= : Post caption}
                            {--media-url=* : HTTPS URL for each media item}
                            {--intake-mode= : studio_draft, autopilot_queue, or publish_now}
                            {--idempotency-key= : Optional Idempotency-Key header value}';

    protected $description = 'Push a social post from this site to GrowthAtlas Social Hub';

    public function handle(SocialClient $client): int
    {
        $externalId = (string) $this->option('external-id');
        $format = (string) $this->option('format');
        $mediaUrls = array_values(array_filter((array) $this->option('media-url')));

        if ($externalId === '' || $format === '' || $mediaUrls === []) {
            $this->error('Required options: --external-id, --format, and at least one --media-url');

            return self::FAILURE;
        }

        $payload = [
            'external_id' => $externalId,
            'format' => $format,
            'media' => array_map(
                static fn (string $url): array => ['url' => $url],
                $mediaUrls,
            ),
        ];

        if (($caption = $this->option('caption')) !== null && $caption !== '') {
            $payload['caption'] = (string) $caption;
        }

        if (($intakeMode = $this->option('intake-mode')) !== null && $intakeMode !== '') {
            $payload['intake_mode'] = (string) $intakeMode;
        }

        try {
            $response = $client->pushPost(
                $payload,
                $this->option('idempotency-key') ?: null,
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $data = $response['data'] ?? $response;
        $this->info('Social post pushed to GrowthAtlas.');
        $this->line('GrowthAtlas post id: '.($data['id'] ?? 'unknown'));
        $this->line('External id: '.($data['external_id'] ?? $externalId));
        $this->line('Status: '.($data['status'] ?? 'unknown'));

        if (! empty($data['studio_url'])) {
            $this->line('Studio URL: '.$data['studio_url']);
        }

        return self::SUCCESS;
    }
}
