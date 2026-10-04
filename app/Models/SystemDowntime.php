<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemDowntime extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
