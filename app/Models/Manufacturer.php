<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Manufacturer extends BaseModel
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'contact_person',
        'email',
        'phone',
        'address',
        'added_by',
        'last_updated_by',
    ];

    protected $dates = ['created_at', 'updated_at'];
}
