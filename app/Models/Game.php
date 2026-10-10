<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $primaryKey = 'appid';

public $incrementing = false;

public $timestamps = false;

protected $guarded = [];

protected function casts(): array
{
    return [
        'release_date' => 'date:Y-m-d',
        'is_free' => 'boolean',
        'price' => 'float',
        'positive_pct' => 'float',
    ];
}
}
