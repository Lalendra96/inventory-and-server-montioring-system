<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_node_id', 'actor_id', 'action', 'target_type', 'target', 'signal_or_operation',
        'reason', 'successful', 'exit_code', 'result_summary', 'executed_at',
    ];

    protected function casts(): array
    {
        return ['successful' => 'boolean', 'executed_at' => 'datetime'];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(ServerNode::class, 'server_node_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
