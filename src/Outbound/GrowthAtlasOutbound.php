<?php

namespace GrowthAtlas\Connector\Outbound;

class GrowthAtlasOutbound
{
    public function __construct(
        private readonly SocialClient $social,
    ) {}

    public function social(): SocialClient
    {
        return $this->social;
    }
}
