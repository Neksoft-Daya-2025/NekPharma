<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DcrDoctorVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'dcr_report_id',
        'doctor_id',
        'doctor_name',
        'doctor_mobile',
        'doctor_email',
        'qualification',
        'speciality',
        'area',
        'msl',
        'product1',
        'samples_unit1',
        'pob1',
        'remark1',
        'product2',
        'samples_unit2',
        'pob2',
        'remark2',
        'product3',
        'samples_unit3',
        'pob3',
        'remark3',
        'input1',
        'input2',
        'pob',
        'general_remark',
        'latitude',
        'longitude',
    ];

    public function dcrReport()
    {
        return $this->belongsTo(DcrReport::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
