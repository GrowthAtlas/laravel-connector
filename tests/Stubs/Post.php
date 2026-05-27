<?php

namespace GrowthAtlas\Connector\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $guarded = []; // intentionally uses $guarded to test the idempotency fix
}
