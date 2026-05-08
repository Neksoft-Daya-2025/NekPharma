<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'fullname',
        'email',
        'qualification',
        'speciality',
        'mobile',
        'dob',
        'dom',
        'area',
        'area_id',
        'headquarter_id',
        'exstation_id',
        'outstation_id',
        'gender',
        'doctor_type',
        'address',
        'doctor_pic',
        'msl_number',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'dob' => 'date',
        'dom' => 'date',
    ];

    // Relationships
    public function area()
    {
        return $this->belongsTo(PharmaArea::class, 'area_id');
    }

    public function headquarter()
    {
        return $this->belongsTo(PharmaHeadquarter::class, 'headquarter_id');
    }

    public function exstation()
    {
        return $this->belongsTo(PharmaExstation::class, 'exstation_id');
    }

    public function outstation()
    {
        return $this->belongsTo(PharmaOutstation::class, 'outstation_id');
    }

    public function sfcChartItems()
    {
        return $this->belongsToMany(\Modules\SFC\Entities\SFCChartItem::class, 'sfc_chart_item_doctors', 'doctor_id', 'sfc_chart_item_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'doctor_products', 'doctor_id', 'product_id')->withTimestamps();
    }

    /**
     * Scope to filter by doctor type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('doctor_type', $type);
    }

    /**
     * Scope to filter VIP doctors
     */
    public function scopeVip($query)
    {
        return $query->where('doctor_type', 'VIP');
    }

    /**
     * Scope to filter CORE doctors
     */
    public function scopeCore($query)
    {
        return $query->where('doctor_type', 'CORE');
    }

    /**
     * Get all unique doctor types for the company
     */
    public static function getDoctorTypes($companyId = null)
    {
        $companyId = $companyId ?? company()->id;
        return static::where('company_id', $companyId)
            ->whereNotNull('doctor_type')
            ->distinct()
            ->pluck('doctor_type')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }
}
