<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetricSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_node_id', 'captured_at', 'cpu_percent', 'memory_total_bytes', 'memory_used_bytes',
        'memory_percent', 'disk_total_bytes', 'disk_used_bytes', 'disk_percent', 'load_1', 'load_5',
        'load_15', 'process_count', 'uptime_seconds', 'reachable', 'error_code', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'reachable' => 'boolean',
            'cpu_percent' => 'float',
            'memory_percent' => 'float',
            'disk_percent' => 'float',
            'load_1' => 'float',
            'load_5' => 'float',
            'load_15' => 'float',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(ServerNode::class, 'server_node_id');
    }
}
