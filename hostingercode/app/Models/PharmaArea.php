<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaArea extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = ['company_id', 'region_id', 'name'];

    public function region()
    {
        return $this->belongsTo(PharmaRegion::class);
    }

    public function headquarters()
    {
        return $this->hasMany(PharmaHeadquarter::class, 'area_id');
    }
}
