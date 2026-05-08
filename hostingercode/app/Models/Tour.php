<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'date',
        'day',
        'user_id',
        'headquarter_id',
        'work_with',
        'work_status',
        'station',
        'remark',
        'approved',
        'approved_by',
        'approved_at',
        'status',
        'submitted_to',
    ];

    protected $casts = [
        'date' => 'date',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function headquarter()
    {
        return $this->belongsTo(PharmaHeadquarter::class);
    }

    public function workingWith()
    {
        return $this->belongsTo(User::class, 'work_with');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function submittedTo()
    {
        return $this->belongsTo(User::class, 'submitted_to');
    }
}
