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
use GrowthAtlas\Connector\Support\VersionChecker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * GrowthAtlas Connector Status — Filament admin page.
 *
 * Register in your panel provider via GrowthAtlasConnectorPlugin::make().
 *
 * Everything (API key, signing secret, logging, outbound Social credentials)
 * is managed from this page and stored in the database — no .env editing required.
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

        $outboundToken = Settings::outboundInboundToken();
        $outboundBase = Settings::outboundApiBase();

        return [
            'apiKeyConfigured'   => ! empty($apiKey),
            'apiKeyManaged'      => Settings::isManaged('api_key'),
            'apiKeyMasked'       => $apiKey ? $this->mask($apiKey) : null,
            'signingConfigured'  => ! empty(Settings::signingSecret()),
            'signingManaged'     => Settings::isManaged('signing_secret'),
            'logEnabled'         => $logEnabled,
            'outboundTokenConfigured' => ! empty($outboundToken),
            'outboundTokenManaged'    => Settings::isManaged('outbound_inbound_token'),
            'outboundTokenMasked'     => $outboundToken ? $this->mask($outboundToken) : null,
            'outboundApiBase'         => $outboundBase,
            'outboundApiBaseManaged'  => Settings::isManaged('outbound_api_base'),
            'lastInbound'        => $lastInbound,
            'recentRequests'     => $recent,
            'receivedContent'    => $received,
            'healthUrl'          => $this->healthUrl(),
            'versionStatus'      => VersionChecker::status(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->testConnectionAction(),
            $this->testOutboundSocialAction(),
            $this->manageApiKeyAction(),
            $this->manageSigningSecretAction(),
            $this->manageOutboundTokenAction(),
            $this->manageOutboundApiBaseAction(),
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


    protected function testOutboundSocialAction(): Action
    {
        return Action::make('test_outbound_social')
            ->label('Test outbound Social')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Outbound Social test')
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view(
                'growthatlas-connector::filament.health-result',
                ['result' => $this->runOutboundSocialCheck()],
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

    protected function manageOutboundTokenAction(): Action
    {
        return Action::make('manage_outbound_token')
            ->label('Outbound token')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Outbound Social inbound token')
            ->modalDescription('Paste the ga_in_… token from GrowthAtlas → Integration → Inbound Social. Used when this site pushes posts to Social Hub.')
            ->schema([
                TextInput::make('outbound_inbound_token')
                    ->label('Inbound token')
                    ->password()
                    ->revealable()
                    ->autocomplete(false)
                    ->placeholder('ga_in_…')
                    ->helperText('Leave blank and enable “Clear token” to remove a stored value.'),
                Toggle::make('clear')
                    ->label('Clear token')
                    ->live()
                    ->default(false),
            ])
            ->action(function (array $data) {
                if (! empty($data['clear'])) {
                    Settings::set('outbound_inbound_token', null);

                    Notification::make()
                        ->title('Outbound token cleared')
                        ->warning()
                        ->send();

                    return;
                }

                $token = trim((string) ($data['outbound_inbound_token'] ?? ''));
                if ($token === '') {
                    Notification::make()
                        ->title('No token entered')
                        ->body('Paste a ga_in_… token, or enable Clear token to remove the stored value.')
                        ->danger()
                        ->send();

                    return;
                }

                Settings::set('outbound_inbound_token', $token);

                Notification::make()
                    ->title('Outbound token saved')
                    ->body('This site can now push social posts to GrowthAtlas.')
                    ->success()
                    ->send();
            });
    }

    protected function manageOutboundApiBaseAction(): Action
    {
        return Action::make('manage_outbound_api_base')
            ->label('API base URL')
            ->icon('heroicon-o-globe-alt')
            ->color('gray')
            ->modalHeading('GrowthAtlas API base URL')
            ->modalDescription('Base URL for outbound Social pushes. Default is https://growthatlas.io.')
            ->schema([
                TextInput::make('outbound_api_base')
                    ->label('API base URL')
                    ->url()
                    ->default(fn () => Settings::outboundApiBase())
                    ->required()
                    ->helperText('No trailing slash required.'),
            ])
            ->action(function (array $data) {
                $base = rtrim(trim((string) ($data['outbound_api_base'] ?? '')), '/');
                if ($base === '') {
                    $base = 'https://growthatlas.io';
                }

                Settings::set('outbound_api_base', $base);

                Notification::make()
                    ->title('API base URL saved')
                    ->body($base)
                    ->success()
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


    /**
     * @return array<string, mixed>
     */
    protected function runOutboundSocialCheck(): array
    {
        $url = Settings::outboundApiBase().'/api/inbound/v1/health';

        try {
            $token = Settings::outboundInboundToken();
            if ($token === null || $token === '') {
                return [
                    'ok' => false,
                    'status' => null,
                    'url' => $url,
                    'data' => null,
                    'error' => 'Outbound inbound token is not set. Use “Outbound token” above first.',
                    'title_ok' => 'Outbound Social connected',
                    'title_bad' => 'Outbound Social failed',
                    'subtitle_ok' => 'This site can push posts to GrowthAtlas Social Hub.',
                ];
            }

            $response = Http::timeout(12)
                ->withToken($token)
                ->acceptJson()
                ->get($url);

            $data = $response->json('data') ?? $response->json();
            $message = is_array($data) ? ($data['message'] ?? null) : null;

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'url' => $url,
                'data' => is_array($data) ? $data : null,
                'error' => $response->successful()
                    ? null
                    : ($message ?: ('HTTP '.$response->status())),
                'title_ok' => 'Outbound Social connected',
                'title_bad' => 'Outbound Social failed',
                'subtitle_ok' => 'This site can push posts to GrowthAtlas Social Hub.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => null,
                'url' => $url,
                'data' => null,
                'error' => $e->getMessage(),
                'title_ok' => 'Outbound Social connected',
                'title_bad' => 'Outbound Social failed',
                'subtitle_ok' => 'This site can push posts to GrowthAtlas Social Hub.',
            ];
        }
    }

    protected function runHealthCheck(): array
    {
        $url = $this->healthUrl();

        try {
            $response = Http::timeout(8)->acceptJson()->get($url);
            $data = $response->json('data') ?? $response->json();
            $message = is_array($data) ? ($data['message'] ?? null) : null;

            return [
                'ok'      => $response->successful(),
                'status'  => $response->status(),
                'url'     => $url,
                'data'    => $data,
                'error'   => $response->successful()
                    ? null
                    : ($message ?: ('HTTP ' . $response->status())),
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
