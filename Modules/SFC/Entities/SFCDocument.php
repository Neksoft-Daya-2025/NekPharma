<?php

namespace Modules\SFC\Entities;

use App\Models\BaseModel;
use App\Traits\HasCompany;

class SFCDocument extends BaseModel
{
    use HasCompany;

    protected $table = 'sfc_documents';

    protected $fillable = [
        'company_id',
        'name',
        'headquarter',
        'area',
        'region',
        'vip_dr_count',
        'core_dr_count',
        'total_dr_count',
        'vip_visits_per_month',
        'core_visits_per_month',
        'total_vip_visits_monthly',
        'total_core_visits_monthly',
        'total_visits_monthly',
        'filled_by_name',
        'abm_approval',
        'rbm_approval',
        'abm_approved_at',
        'rbm_approved_at',
        'abm_approved_by',
        'rbm_approved_by',
        'added_by',
    ];

    protected $casts = [
        'vip_dr_count' => 'integer',
        'core_dr_count' => 'integer',
        'total_dr_count' => 'integer',
        'vip_visits_per_month' => 'integer',
        'core_visits_per_month' => 'integer',
        'total_vip_visits_monthly' => 'integer',
        'total_core_visits_monthly' => 'integer',
        'total_visits_monthly' => 'integer',
        'abm_approved_at' => 'datetime',
        'rbm_approved_at' => 'datetime',
    ];

    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by');
    }

    public function abmApprovedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'abm_approved_by');
    }

    public function rbmApprovedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rbm_approved_by');
    }

    public function chartItems()
    {
        return $this->hasMany(SFCChartItem::class, 'sfc_document_id')->orderBy('serial_number');
    }

    /**
     * Calculate and update summary statistics
     */
    public function calculateStatistics()
    {
        $this->total_dr_count = $this->vip_dr_count + $this->core_dr_count;
        $this->total_vip_visits_monthly = $this->vip_dr_count * $this->vip_visits_per_month;
        $this->total_core_visits_monthly = $this->core_dr_count * $this->core_visits_per_month;
        $this->total_visits_monthly = $this->total_vip_visits_monthly + $this->total_core_visits_monthly;
    }
}

