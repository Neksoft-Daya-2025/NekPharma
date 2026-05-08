<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TourWorkStatus extends BaseModel
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tours()
    {
        return $this->hasMany(Tour::class, 'work_status');
    }
}
