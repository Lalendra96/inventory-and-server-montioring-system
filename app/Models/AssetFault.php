<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetFault extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
