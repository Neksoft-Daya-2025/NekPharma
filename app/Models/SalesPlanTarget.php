<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPlanTarget extends BaseModel
{
    use HasCompany, HasFactory;

    protected $table = 'sales_plan_targets';

    protected $fillable = [
        'company_id',
        'period_month',
        'period_year',
        'plan_level',
        'headquarter_id',
        'area_id',
        'region_id',
        'target_amount',
        'target_qty',
        'product_id',
        'notes',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_qty' => 'decimal:2',
    ];

    public function headquarter(): BelongsTo
    {
        return $this->belongsTo(PharmaHeadquarter::class, 'headquarter_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(PharmaArea::class, 'area_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(PharmaRegion::class, 'region_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getScopeNameAttribute(): string
    {
        if ($this->plan_level === 'headquarter' && $this->headquarter) {
            return $this->headquarter->name;
        }
        if ($this->plan_level === 'area' && $this->area) {
            return $this->area->name;
        }
        if ($this->plan_level === 'region' && $this->region) {
            return $this->region->name;
        }
        return '-';
    }
}
