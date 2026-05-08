<?php

namespace App\Models;

use App\Traits\HasCompany;

/**
 * Tour month lock: when a row exists for (company_id, year, month), that month
 * is locked for tour create/edit by non-admin (auto-lock on 25th for next month; admin can unlock).
 */
class TourMonthLock extends BaseModel
{
    use HasCompany;

    protected $table = 'tour_month_locks';

    protected $fillable = [
        'company_id',
        'year',
        'month',
    ];

    /**
     * Check if a given month is locked for tour create/edit (non-admin).
     */
    public static function isLocked(int $companyId, int $year, int $month): bool
    {
        return static::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();
    }
}
