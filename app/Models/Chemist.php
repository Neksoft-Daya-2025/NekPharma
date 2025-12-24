<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chemist extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'shopname',
        'fullname',
        'email',
        'mobile',
        'dob',
        'dom',
        'area',
        'area_id',
        'headquarter_id',
        'exstation_id',
        'outstation_id',
        'gender',
        'address',
        'chemist_pic',
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
}
