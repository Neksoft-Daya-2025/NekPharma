<?php

namespace App\Models;

use App\Traits\HasCompany;

class EnterpriseAuditLog extends BaseModel
{
    use HasCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'actor_id',
        'event',
        'severity',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'meta',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
