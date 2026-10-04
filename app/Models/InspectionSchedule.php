<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionSchedule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'next_due_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
