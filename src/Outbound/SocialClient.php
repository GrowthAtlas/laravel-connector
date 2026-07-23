<?php

namespace GrowthAtlas\Connector\Outbound;

use GrowthAtlas\Connector\Support\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialClient
{
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

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->request('get', '/api/inbound/v1/health', []);
    }

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

    private function requireInboundToken(): string
    {
        $token = Settings::outboundInboundToken();

        if ($token === null || $token === '') {
            throw new RuntimeException(
                'GrowthAtlas inbound token is not configured. Paste it on the GrowthAtlas Connector admin page (Outbound Social), or set GROWTHATLAS_INBOUND_TOKEN as a temporary .env fallback.',
            );
        }

        return $token;
    }

    private function endpoint(string $path): string
    {
        return Settings::outboundApiBase().$path;
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
