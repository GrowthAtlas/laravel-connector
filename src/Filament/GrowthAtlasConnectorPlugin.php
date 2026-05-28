<?php

namespace GrowthAtlas\Connector\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use GrowthAtlas\Connector\Filament\Pages\ConnectorStatus;

/**
 * Filament 4 plugin for the GrowthAtlas Connector.
 *
 * Register in your panel provider:
 *
 *   public function panel(Panel $panel): Panel
 *   {
 *       return $panel
 *           ->plugin(GrowthAtlasConnectorPlugin::make())
 *           // ...
 *   }
 */
class GrowthAtlasConnectorPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'growthatlas-connector';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ConnectorStatus::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
