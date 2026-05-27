<?php

namespace GrowthAtlas\Connector;

use GrowthAtlas\Connector\Http\Middleware\LogInboundRequest;
use Illuminate\Support\ServiceProvider;

class ConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/growthatlas-connector.php',
            'growthatlas-connector',
        );
    }

    public function boot(): void
    {
        $this->registerPublishes();
        $this->registerRoutes();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->bootFilament();
    }

    // ── Publishable assets ────────────────────────────────────────────────────

    private function registerPublishes(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // php artisan vendor:publish --tag=growthatlas-connector-config
        $this->publishes([
            __DIR__ . '/../config/growthatlas-connector.php' => config_path('growthatlas-connector.php'),
        ], 'growthatlas-connector-config');

        // php artisan vendor:publish --tag=growthatlas-connector-migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'growthatlas-connector-migrations');

        // php artisan vendor:publish --tag=growthatlas-connector-views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/growthatlas-connector'),
        ], 'growthatlas-connector-views');
    }

    // ── Routes ────────────────────────────────────────────────────────────────

    private function registerRoutes(): void
    {
        $logEnabled = config('growthatlas-connector.log_inbound', false);
        $middleware = array_merge(
            config('growthatlas-connector.route_middleware', ['api']),
            $logEnabled ? [LogInboundRequest::class] : [],
        );

        $this->app->make('router')
            ->prefix(config('growthatlas-connector.route_prefix', 'api/growthatlas/v1'))
            ->middleware($middleware)
            ->group(__DIR__ . '/../routes/api.php');
    }

    // ── Views ─────────────────────────────────────────────────────────────────

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'growthatlas-connector');
    }

    // ── Filament ──────────────────────────────────────────────────────────────

    private function bootFilament(): void
    {
        if (! config('growthatlas-connector.filament_page', false)) {
            return;
        }

        if (! class_exists(\Filament\Panel::class)) {
            return;
        }

        $this->callAfterResolving('filament', function ($filament) {
            try {
                $panelId = config('growthatlas-connector.filament_panel_id');
                $panels  = $filament->getPanels();

                $panel = $panelId
                    ? ($panels[$panelId] ?? reset($panels))
                    : reset($panels);

                if ($panel) {
                    $panel->pages([\GrowthAtlas\Connector\Filament\Pages\ConnectorStatus::class]);
                }
            } catch (\Throwable) {
                // Never break the app if Filament registration fails.
            }
        });
    }
}
