<?php

namespace GrowthAtlas\Connector\Outbound;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialClient
{
    public function __construct(
        private readonly string $apiBase,
        private readonly ?string $inboundToken,
        private readonly ?string $defaultIntakeMode = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function pushPost(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->request('post', '/api/inbound/v1/social-posts', $payload, $idempotencyKey);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, UploadedFile|string>  $files  Ordered media file paths or UploadedFile instances
     * @return array<string, mixed>
     */
    public function pushPostMultipart(array $payload, array $files = [], ?string $idempotencyKey = null): array
    {
        $token = $this->requireInboundToken();
        $payload = $this->preparePayload($payload);
        $url = $this->endpoint('/api/inbound/v1/social-posts/multipart');

        $pending = Http::withToken($token)
            ->acceptJson();

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $pending = $pending->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        foreach (array_values($files) as $index => $file) {
            if ($file instanceof UploadedFile) {
                $pending = $pending->attach(
                    'media['.$index.']',
                    file_get_contents($file->getRealPath()) ?: '',
                    $file->getClientOriginalName(),
                );

                continue;
            }

            $path = (string) $file;
            $pending = $pending->attach(
                'media['.$index.']',
                file_get_contents($path) ?: '',
                basename($path),
            );
        }

        return $this->decodeResponse($pending->post($url, $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPost(int|string $id): array
    {
        return $this->request('get', '/api/inbound/v1/social-posts/'.$id, []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload, ?string $idempotencyKey = null): array
    {
        $token = $this->requireInboundToken();
        $payload = $this->preparePayload($payload);
        $url = $this->endpoint($path);

        $pending = Http::withToken($token)
            ->acceptJson()
            ->asJson();

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $pending = $pending->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        $response = $method === 'get'
            ? $pending->get($url)
            : $pending->post($url, $payload);

        return $this->decodeResponse($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function preparePayload(array $payload): array
    {
        if (
            ! array_key_exists('intake_mode', $payload)
            && $this->defaultIntakeMode !== null
            && $this->defaultIntakeMode !== ''
        ) {
            $payload['intake_mode'] = $this->defaultIntakeMode;
        }

        return $payload;
    }

    private function requireInboundToken(): string
    {
        $token = $this->inboundToken;

        if ($token === null || $token === '') {
            throw new RuntimeException(
                'GrowthAtlas inbound token is not configured. Set GROWTHATLAS_INBOUND_TOKEN in your .env file.',
            );
        }

        return $token;
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->apiBase, '/').$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $data = $response->json();

        if (! $response->successful()) {
            $message = 'GrowthAtlas inbound API request failed.';

            if (is_array($data)) {
                $message = (string) (Arr::get($data, 'message')
                    ?? Arr::get($data, 'error')
                    ?? $message);
            }

            throw new RuntimeException($message.' (HTTP '.$response->status().')');
        }

        return is_array($data) ? $data : [];
    }
}
