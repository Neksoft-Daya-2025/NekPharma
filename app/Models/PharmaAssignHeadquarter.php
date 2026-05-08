<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmaAssignHeadquarter extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    protected $table = 'pharma_assign_headquarters';

    protected $fillable = [
        'company_id',
        'area_id',
        'headquarter_ids',
    ];

    protected $casts = [
        'headquarter_ids' => 'array',
    ];

    public function area()
    {
        return $this->belongsTo(PharmaArea::class, 'area_id');
    }
}

