<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DcrChemistVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'dcr_report_id',
        'chemist_id',
        'chemist_name',
        'chemist_mobile',
        'chemist_email',
        'area',
        'station',
        'msl',
        'rcpa1',
        'pob_amount1',
        'remark1',
        'rcpa2',
        'pob_amount2',
        'remark2',
        'rcpa3',
        'pob_amount3',
        'remark3',
        'rcpa4',
        'pob_amount4',
        'remark4',
        'input1',
        'input2',
        'input_remark',
        'general_remark',
    ];

    public function dcrReport()
    {
        return $this->belongsTo(DcrReport::class);
    }

    public function chemist()
    {
        return $this->belongsTo(Chemist::class);
    }
}
