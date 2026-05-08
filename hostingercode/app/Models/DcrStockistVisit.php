<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DcrStockistVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'dcr_report_id',
        'stockist_id',
        'stockist_name',
        'stockist_mobile',
        'stockist_email',
        'area',
        'station',
        'msl',
        'pob',
        'contact_person',
        'contact_person_mobile',
        'proprietor',
        'proprietor_mobile',
        'pob_amount',
        'remark',
        'general_remark',
        'latitude',
        'longitude',
    ];

    public function dcrReport()
    {
        return $this->belongsTo(DcrReport::class);
    }

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }
}
