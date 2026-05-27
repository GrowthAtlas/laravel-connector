<?php

namespace GrowthAtlas\Connector\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One logged inbound request from GrowthAtlas.
 * Written by LogInboundRequest middleware when log_inbound = true.
 */
class InboundRequest extends Model
{
    public $timestamps = false;

    protected $table = 'growthatlas_inbound_requests';

    protected $guarded = [];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload_summary' => 'array',
        'created_at'      => 'datetime',
    ];
}
