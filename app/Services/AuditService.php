<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Append a tamper-evident audit record. Audit rows are intentionally never deleted by application code.
     */
    public function record(string $action, ?Model $model = null, array $old = [], array $new = [], array $context = []): AuditLog
    {
        return DB::transaction(function () use ($action, $model, $old, $new, $context) {
            $previousHash = AuditLog::query()->orderByDesc('id')->lockForUpdate()->value('integrity_hash');
            $uuid = (string) Str::uuid();
            $occurredAt = now();
            $requestId = request()->headers->get('X-Request-ID') ?: (string) Str::uuid();

            $payload = [
                'event_uuid' => $uuid,
                'actor_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $model?->getMorphClass(),
                'auditable_id' => $model ? (string) $model->getKey() : null,
                'old_values' => $old,
                'new_values' => $new,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_id' => $requestId,
                'occurred_at' => $occurredAt,
                'previous_hash' => $previousHash,
            ];

            $payload['integrity_hash'] = $this->hash($payload);

            return AuditLog::create($payload);
        });
    }

    public function verify(AuditLog $log): bool
    {
        $payload = $log->only([
            'event_uuid', 'actor_id', 'action', 'auditable_type', 'auditable_id',
            'old_values', 'new_values', 'ip_address', 'user_agent', 'request_id',
            'occurred_at', 'previous_hash',
        ]);

        $payload['occurred_at'] = $log->occurred_at;

        return hash_equals($log->integrity_hash, $this->hash($payload));
    }

    private function hash(array $payload): string
    {
        $secret = (string) config('app.audit_hmac_key', config('app.key'));
        ksort($payload);

        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);
    }
}
