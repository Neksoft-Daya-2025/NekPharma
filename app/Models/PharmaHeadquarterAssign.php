<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaHeadquarterAssign extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'headquarter_id',
        'station',
        'station_id',
    ];

    public function headquarter()
    {
        return $this->belongsTo(PharmaHeadquarter::class, 'headquarter_id');
    }

    public function exstation()
    {
        return $this->belongsTo(PharmaExstation::class, 'station_id');
    }

    public function outstation()
    {
        return $this->belongsTo(PharmaOutstation::class, 'station_id');
    }
}
