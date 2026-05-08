<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaRegion extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = ['company_id', 'zone_id', 'name'];

    public function zone()
    {
        return $this->belongsTo(PharmaZone::class);
    }

    public function areas()
    {
        return $this->hasMany(PharmaArea::class, 'region_id');
    }
}
