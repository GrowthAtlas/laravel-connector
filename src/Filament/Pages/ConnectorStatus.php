<?php

namespace GrowthAtlas\Connector\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use GrowthAtlas\Connector\Models\InboundRequest;
use Illuminate\Support\Str;

/**
 * GrowthAtlas Connector Status — Filament admin page.
 *
 * Register in your panel provider via GrowthAtlasConnectorPlugin::make().
 *
 * Shows:
 *   - API key + signing secret configuration status (x-filament::badge)
 *   - Last inbound request timestamp
 *   - Recent 20 inbound requests (requires log_inbound = true + migration)
 *   - "Rotate signing secret" action
 *   - "Open /health" button
 */
class ConnectorStatus extends Page
{
    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-signal';
    protected static ?string                 $navigationLabel = 'GrowthAtlas';
    protected static string|\UnitEnum|null   $navigationGroup = 'Integrations';
    protected string                         $view            = 'growthatlas-connector::filament.connector-status';
    protected static ?int                    $navigationSort  = 90;

    public function getTitle(): string
    {
        return 'GrowthAtlas Connector';
    }

    public function getViewData(): array
    {
        $apiKey     = config('growthatlas-connector.api_key');
        $sigSecret  = config('growthatlas-connector.signing_secret');
        $logEnabled = config('growthatlas-connector.log_inbound', false);
        $prefix     = trim(config('growthatlas-connector.route_prefix', 'api/growthatlas/v1'), '/');

        $recent      = collect();
        $lastInbound = null;

        if ($logEnabled && \Illuminate\Support\Facades\Schema::hasTable('growthatlas_inbound_requests')) {
            $recent      = InboundRequest::latest('created_at')->limit(20)->get();
            $lastInbound = $recent->first()?->created_at;
        }

        return [
            'apiKeyConfigured'    => ! empty($apiKey),
            'apiKeyMasked'        => $apiKey ? substr($apiKey, 0, 8) . str_repeat('*', max(0, strlen($apiKey) - 8)) : null,
            'signingConfigured'   => ! empty($sigSecret),
            'logEnabled'          => $logEnabled,
            'lastInbound'         => $lastInbound,
            'recentRequests'      => $recent,
            'healthUrl'           => url($prefix . '/health'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $prefix = trim(config('growthatlas-connector.route_prefix', 'api/growthatlas/v1'), '/');

        return [
            Action::make('rotate_secret')
                ->label('Rotate signing secret')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Rotate Signing Secret')
                ->modalDescription(
                    'Generates a new random secret and writes it to .env. ' .
                    'You MUST copy the new value into your GrowthAtlas dashboard ' .
                    'immediately — requests will fail until both sides match.'
                )
                ->action(function () {
                    $newSecret  = Str::random(64);
                    $envPath    = base_path('.env');
                    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

                    if (str_contains($envContent, 'GROWTHATLAS_SIGNING_SECRET=')) {
                        $envContent = preg_replace(
                            '/^GROWTHATLAS_SIGNING_SECRET=.*/m',
                            "GROWTHATLAS_SIGNING_SECRET={$newSecret}",
                            $envContent,
                        );
                    } else {
                        $envContent .= "\nGROWTHATLAS_SIGNING_SECRET={$newSecret}";
                    }

                    file_put_contents($envPath, $envContent);

                    Notification::make()
                        ->title('Signing secret rotated')
                        ->body("New secret (copy to GrowthAtlas now):\n{$newSecret}")
                        ->warning()
                        ->persistent()
                        ->send();
                }),

            Action::make('open_health')
                ->label('Open /health')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(fn () => url($prefix . '/health'))
                ->openUrlInNewTab(),
        ];
    }
}
