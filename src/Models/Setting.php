<?php

namespace GrowthAtlas\Connector\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single key/value connector setting, managed from the Filament admin page.
 *
 * @property string      $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $table = 'growthatlas_settings';

    protected $guarded = [];
}
