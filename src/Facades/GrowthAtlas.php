<?php

namespace GrowthAtlas\Connector\Facades;

use GrowthAtlas\Connector\Outbound\GrowthAtlasOutbound;
use GrowthAtlas\Connector\Outbound\SocialClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SocialClient social()
 *
 * @see GrowthAtlasOutbound
 */
class GrowthAtlas extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GrowthAtlasOutbound::class;
    }
}
