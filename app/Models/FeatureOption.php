<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureOption extends Model
{
    protected $fillable = ['key', 'label', 'description', 'enabled', 'settings', 'updated_by'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'settings' => 'array'];
    }
}
