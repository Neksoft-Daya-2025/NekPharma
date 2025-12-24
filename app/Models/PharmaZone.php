<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaZone extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = ['company_id', 'name'];

    public function regions()
    {
        return $this->hasMany(PharmaRegion::class, 'zone_id');
    }
}
