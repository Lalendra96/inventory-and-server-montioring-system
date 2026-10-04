<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deployed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
