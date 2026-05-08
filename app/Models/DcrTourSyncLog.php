<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DcrTourSyncLog extends Model
{
    protected $table = 'dcr_tour_sync_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'report_date',
        'dcr_report_id',
        'tour_id',
        'action',
        'old_value',
        'new_value',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'meta' => 'array',
    ];
}
