<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaHeadquarter extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = ['company_id', 'area_id', 'name'];

    public function area()
    {
        return $this->belongsTo(PharmaArea::class);
    }

    public function stationAssigns()
    {
        return $this->hasMany(PharmaHeadquarterAssign::class, 'headquarter_id');
    }

    public function exstations()
    {
        return $this->belongsToMany(PharmaExstation::class, 'pharma_headquarter_assigns', 'headquarter_id', 'station_id')
            ->wherePivot('station', 'exstation');
    }

    public function outstations()
    {
        return $this->belongsToMany(PharmaOutstation::class, 'pharma_headquarter_assigns', 'headquarter_id', 'station_id')
            ->wherePivot('station', 'outstation');
    }
}
