<?php

namespace GrowthAtlas\Connector\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One article received from GrowthAtlas (created or updated on this site).
 *
 * @property int|null    $growthatlas_draft_id
 * @property int|null    $growthatlas_brief_id
 * @property string|null $external_id
 * @property string|null $title
 * @property string|null $url
 * @property string|null $growthatlas_url
 * @property string|null $status
 * @property int|null    $seo_score
 * @property int         $update_count
 */
class ReceivedContent extends Model
{
    protected $table = 'growthatlas_received_content';

    protected $guarded = [];

    protected $casts = [
        'growthatlas_draft_id' => 'integer',
        'growthatlas_brief_id' => 'integer',
        'seo_score'            => 'integer',
        'update_count'         => 'integer',
        'last_action_at'       => 'datetime',
    ];
}
