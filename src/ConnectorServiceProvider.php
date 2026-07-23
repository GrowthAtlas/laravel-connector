<?php

namespace GrowthAtlas\Connector;

use GrowthAtlas\Connector\Console\PushSocialPostCommand;
use GrowthAtlas\Connector\Http\Middleware\LogInboundRequest;
use GrowthAtlas\Connector\Outbound\GrowthAtlasOutbound;
use GrowthAtlas\Connector\Outbound\SocialClient;
use Illuminate\Support\ServiceProvider;

class ConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/growthatlas-connector.php',
            'growthatlas-connector',
        );

        $this->app->singleton(SocialClient::class, function () {
            return new SocialClient;
        });

        $this->app->singleton(GrowthAtlasOutbound::class, function ($app) {
            return new GrowthAtlasOutbound($app->make(SocialClient::class));
        });
    }

    public function boot(): void
    {
        $this->registerPublishes();
        $this->registerRoutes();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PushSocialPostCommand::class,
        ]);
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
        // The logging middleware is always attached; it decides at runtime
        // whether to record based on the (DB-managed) log_inbound setting.
        $middleware = array_merge(
            config('growthatlas-connector.route_middleware', ['api']),
            [LogInboundRequest::class],
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
}
