<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SRS 3.1.3: Monthly attendance finalisation – payroll can be generated only after finalise.
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $year
 * @property int $month
 * @property \Illuminate\Support\Carbon|null $finalised_at
 * @property int|null $finalised_by
 */
class AttendanceMonthFinalisation extends BaseModel
{
    use HasCompany;

    protected $table = 'attendance_month_finalisations';

    protected $casts = [
        'finalised_at' => 'datetime',
    ];

    protected $fillable = [
        'company_id',
        'year',
        'month',
        'finalised_at',
        'finalised_by',
    ];

    public function finalisedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalised_by');
    }

    /**
     * Check if attendance for the given company, year, month is finalised.
     */
    public static function isFinalised(?int $companyId, int $year, int $month): bool
    {
        $companyId = $companyId ?? company()->id;

        return static::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->whereNotNull('finalised_at')
            ->exists();
    }
}
