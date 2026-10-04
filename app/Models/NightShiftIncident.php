<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightShiftIncident extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
