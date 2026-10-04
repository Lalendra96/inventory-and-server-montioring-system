<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServerNode extends Model
{
    protected $fillable = [
        'code', 'name', 'host', 'ssh_port', 'ssh_user', 'ssh_key_path', 'environment',
        'system_group', 'os_hint', 'allowed_services', 'protected_process_patterns', 'allow_process_control',
        'allow_service_control', 'poll_interval_seconds', 'notes', 'is_active', 'last_seen_at',
        'disabled_at', 'disabled_by', 'disable_reason',
    ];

    protected function casts(): array
    {
        return [
            'allowed_services' => 'array',
            'protected_process_patterns' => 'array',
            'allow_process_control' => 'boolean',
            'allow_service_control' => 'boolean',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ServerMetricSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(ServerMetricSnapshot::class)->latestOfMany('captured_at');
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(ServerActionLog::class);
    }
}
