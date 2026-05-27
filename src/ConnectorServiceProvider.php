<?php

namespace GrowthAtlas\Connector;

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
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/growthatlas-connector.php' => config_path('growthatlas-connector.php'),
        ], 'growthatlas-connector-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/growthatlas-connector'),
        ], 'growthatlas-connector-views');

        // Publish Filament page
        $this->publishes([
            __DIR__ . '/Filament' => app_path('Filament/Pages'),
        ], 'growthatlas-connector-filament');

        // Register routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}
