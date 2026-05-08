<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DcrReport extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'submitted_to',
        'approved',
        'approved_by',
        'approved_at',
        'status',
        'report_date',
        // Common fields
        'headquarter',
        'station',
        'work_status',
        'work_with',
        // Doctor visit
        'doctor_id',
        'doctor_msl',
        'doctor_area',
        'speciality',
        // Products with Samples Unit, POB and remarks
        'product1',
        'samples_unit1',
        'pob_doctor1',
        'doctor_remark1',
        'product2',
        'samples_unit2',
        'pob_doctor2',
        'doctor_remark2',
        'product3',
        'samples_unit3',
        'pob_doctor3',
        'doctor_remark3',
        'input1',
        'input2',
        'pob',
        'doctor_general_remark',
        // Chemist visit
        'chemist_id',
        'chemist_msl',
        'chemist_area',
        'chemist_station',
        'chemist_general_remark',
        // RCPA with amounts and remarks
        'rcpa1',
        'chemist_pob_amount1',
        'chemist_remark1',
        'rcpa2',
        'chemist_pob_amount2',
        'chemist_remark2',
        'rcpa3',
        'chemist_pob_amount3',
        'chemist_remark3',
        'rcpa4',
        'chemist_pob_amount4',
        'chemist_remark4',
        'chemist_input1',
        'chemist_input2',
        'chemist_input_remark',
        // Stockist visit
        'stockist_id',
        'stockist_msl',
        'stockist_area',
        'stockist_station',
        'pob_stockist',
        'contact_person',
        'contact_person_mobile',
        'proprietor',
        'proprietor_mobile',
        'stockist_pob_amount',
        'stockist_remark',
        'stockist_general_remark',
        'remark',
    ];

    protected $casts = [
        'report_date' => 'date',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function chemist()
    {
        return $this->belongsTo(Chemist::class);
    }

    public function stockist()
    {
        return $this->belongsTo(Stockist::class);
    }
    
    // Multiple visits relationships
    public function doctorVisits()
    {
        return $this->hasMany(DcrDoctorVisit::class);
    }
    
    public function chemistVisits()
    {
        return $this->hasMany(DcrChemistVisit::class);
    }
    
    public function stockistVisits()
    {
        return $this->hasMany(DcrStockistVisit::class);
    }
    
    public function submittedTo()
    {
        return $this->belongsTo(User::class, 'submitted_to');
    }
    
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
