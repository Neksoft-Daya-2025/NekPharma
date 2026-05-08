<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaExstation extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = ['company_id', 'name'];

    public function headquarters()
    {
        return $this->belongsToMany(PharmaHeadquarter::class, 'pharma_headquarter_assigns', 'station_id', 'headquarter_id')
            ->wherePivot('station', 'exstation');
    }
}
