<?php

namespace GrowthAtlas\Connector\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use GrowthAtlas\Connector\Http\Controllers\ConnectorController;
use GrowthAtlas\Connector\Models\InboundRequest;
use GrowthAtlas\Connector\Models\ReceivedContent;
use GrowthAtlas\Connector\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GrowthAtlas Connector Status — Filament admin page.
 *
 * Register in your panel provider via GrowthAtlasConnectorPlugin::make().
 *
 * Everything (API key, signing secret, logging) is managed from this page and
 * stored in the database — no .env editing required.
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
        $apiKey     = Settings::apiKey();
        $logEnabled = Settings::loggingEnabled();

        $recent      = collect();
        $lastInbound = null;

        if ($logEnabled && Schema::hasTable('growthatlas_inbound_requests')) {
            $recent      = InboundRequest::latest('created_at')->limit(20)->get();
            $lastInbound = $recent->first()?->created_at;
        }

        $received = collect();
        if (Schema::hasTable('growthatlas_received_content')) {
            $received = ReceivedContent::orderByDesc('last_action_at')->limit(50)->get();
        }

        return [
            'apiKeyConfigured'   => ! empty($apiKey),
            'apiKeyManaged'      => Settings::isManaged('api_key'),
            'apiKeyMasked'       => $apiKey ? $this->mask($apiKey) : null,
            'signingConfigured'  => ! empty(Settings::signingSecret()),
            'signingManaged'     => Settings::isManaged('signing_secret'),
            'logEnabled'         => $logEnabled,
            'lastInbound'        => $lastInbound,
            'recentRequests'     => $recent,
            'receivedContent'    => $received,
            'healthUrl'          => $this->healthUrl(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->testConnectionAction(),
            $this->manageApiKeyAction(),
            $this->manageSigningSecretAction(),
            $this->toggleLoggingAction(),
        ];
    }

    // ── Actions ────────────────────────────────────────────────────────────────

    protected function testConnectionAction(): Action
    {
        return Action::make('test_connection')
            ->label('Test connection')
            ->icon('heroicon-o-bolt')
            ->color('success')
            ->modalHeading('Connection test')
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view(
                'growthatlas-connector::filament.health-result',
                ['result' => $this->runHealthCheck()],
            ));
    }

    protected function manageApiKeyAction(): Action
    {
        return Action::make('manage_api_key')
            ->label('Set API key')
            ->icon('heroicon-o-key')
            ->color('primary')
            ->modalHeading('GrowthAtlas API key')
            ->modalDescription('This key authenticates requests from GrowthAtlas. Paste the same value into your GrowthAtlas dashboard.')
            ->schema([
                TextInput::make('api_key')
                    ->label('API key')
                    ->password()
                    ->revealable()
                    ->autocomplete(false)
                    ->helperText('Leave blank and save to generate a secure random key.'),
            ])
            ->action(function (array $data) {
                $key = trim((string) ($data['api_key'] ?? ''));
                if ($key === '') {
                    $key = Str::random(48);
                }

                Settings::set('api_key', $key);

                Notification::make()
                    ->title('API key saved')
                    ->body("Copy this into GrowthAtlas now:\n{$key}")
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    protected function manageSigningSecretAction(): Action
    {
        return Action::make('manage_signing_secret')
            ->label('Signing secret')
            ->icon('heroicon-o-shield-check')
            ->color('warning')
            ->modalHeading('HMAC signing secret')
            ->modalDescription('Optional. When set, every request must carry a valid HMAC-SHA256 signature. It must match the secret stored in GrowthAtlas.')
            ->schema([
                TextInput::make('signing_secret')
                    ->label('Signing secret')
                    ->password()
                    ->revealable()
                    ->autocomplete(false)
                    ->helperText('Leave blank and save to generate a new random secret.')
                    ->disabled(fn (callable $get) => (bool) $get('disable')),
                Toggle::make('disable')
                    ->label('Disable signing (clear the secret)')
                    ->live()
                    ->default(false),
            ])
            ->action(function (array $data) {
                if (! empty($data['disable'])) {
                    Settings::set('signing_secret', null);

                    Notification::make()
                        ->title('Signing disabled')
                        ->body('HMAC signature verification is now off.')
                        ->warning()
                        ->send();

                    return;
                }

                $secret = trim((string) ($data['signing_secret'] ?? ''));
                if ($secret === '') {
                    $secret = Str::random(64);
                }

                Settings::set('signing_secret', $secret);

                Notification::make()
                    ->title('Signing secret saved')
                    ->body("Copy this into GrowthAtlas now:\n{$secret}")
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }

    protected function toggleLoggingAction(): Action
    {
        $enabled = Settings::loggingEnabled();

        return Action::make('toggle_logging')
            ->label($enabled ? 'Disable request logging' : 'Enable request logging')
            ->icon($enabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading($enabled ? 'Disable request logging' : 'Enable request logging')
            ->modalDescription($enabled
                ? 'Stop recording inbound requests from GrowthAtlas.'
                : 'Record inbound requests from GrowthAtlas for the audit trail below. Requires the connector migrations to have been run.')
            ->action(function () use ($enabled) {
                Settings::set('log_inbound', $enabled ? '0' : '1');

                Notification::make()
                    ->title($enabled ? 'Request logging disabled' : 'Request logging enabled')
                    ->success()
                    ->send();
            });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function runHealthCheck(): array
    {
        $url = $this->healthUrl();

        try {
            $response = Http::timeout(8)->acceptJson()->get($url);

            return [
                'ok'      => $response->successful(),
                'status'  => $response->status(),
                'url'     => $url,
                'data'    => $response->json('data') ?? $response->json(),
                'error'   => $response->successful() ? null : ('HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'     => false,
                'status' => null,
                'url'    => $url,
                'data'   => null,
                'error'  => $e->getMessage(),
            ];
        }
    }

    protected function healthUrl(): string
    {
        $prefix = trim(config('growthatlas-connector.route_prefix', 'api/growthatlas/v1'), '/');

        return url($prefix . '/health');
    }

    protected function mask(string $value): string
    {
        $visible = substr($value, 0, 6);

        return $visible . str_repeat('•', max(4, min(24, strlen($value) - 6)));
    }
}
