<?php

namespace Modules\SFC\Entities;

use App\Models\BaseModel;
use App\Traits\HasCompany;

class SFCChart extends BaseModel
{
    use HasCompany;

    protected $table = 'sfc_charts';

    protected $fillable = [
        'company_id',
        'territory_name',
        'headquarter',
        'covered_from',
        'town_name',
        'one_way_km_actual',
        'grace',
        'total_km',
        'two_way_fare',
        'one_way_fare',
        'ex_hq_os',
        'mode_of_travel',
        'time_in_hours',
        'no_of_days_monthly',
        'vip_dr_count',
        'core_dr_count',
        'total_dr_count',
        'stockist_name',
        'current_business',
        'approx_business_expected',
        'remarks',
        'added_by',
    ];

    protected $casts = [
        'one_way_km_actual' => 'decimal:2',
        'grace' => 'decimal:2',
        'total_km' => 'decimal:2',
        'two_way_fare' => 'decimal:2',
        'one_way_fare' => 'decimal:2',
        'time_in_hours' => 'decimal:2',
        'no_of_days_monthly' => 'integer',
        'vip_dr_count' => 'integer',
        'core_dr_count' => 'integer',
        'total_dr_count' => 'integer',
        'current_business' => 'decimal:2',
        'approx_business_expected' => 'decimal:2',
    ];

    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by');
    }
}

