<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'performed_at' => 'datetime',
            'results' => 'array',
        ];
    }
}
