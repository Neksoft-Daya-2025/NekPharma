<?php

namespace App\Support;

use App\Models\EnterpriseAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EnterpriseAudit
{
    public static function record(
        string $event,
        ?Model $auditable = null,
        array $before = [],
        array $after = [],
        array $meta = [],
        string $severity = 'info'
    ): void {
        try {
            EnterpriseAuditLog::create([
                'company_id' => company()?->id ?? user()?->company_id,
                'actor_id' => user()?->id,
                'event' => $event,
                'severity' => $severity,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'before' => $before ?: null,
                'after' => $after ?: null,
                'meta' => $meta ?: null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Enterprise audit log write failed', [
                'event' => $event,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->getKey(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
