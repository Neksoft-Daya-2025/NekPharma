<?php

namespace Modules\SFC\Entities;

use App\Models\BaseModel;

class SFCChartItem extends BaseModel
{
    protected $table = 'sfc_chart_items';

    protected $fillable = [
        'sfc_document_id',
        'serial_number',
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
        'sort_order',
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
        'serial_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(SFCDocument::class, 'sfc_document_id');
    }

    public function doctors()
    {
        return $this->belongsToMany(\App\Models\Doctor::class, 'sfc_chart_item_doctors', 'sfc_chart_item_id', 'doctor_id');
    }

    /**
     * Calculate total DR count
     */
    public function calculateTotalDrCount()
    {
        $this->total_dr_count = ($this->vip_dr_count ?? 0) + ($this->core_dr_count ?? 0);
    }
}

