<?php

namespace Modules\Letter\Entities;

use App\Models\BaseModel;
use App\Models\ModuleSetting;
use App\Traits\HasCompany;

class LetterSetting extends BaseModel
{
    use HasCompany;

    protected $guarded = ['id'];

    protected $fillable = ['background_image', 'company_id'];

    protected $appends = ['background_image_url'];

    const MODULE_NAME = 'letter';

    public function getBackgroundImageUrlAttribute()
    {
        return ($this->background_image) ? asset_url_local_s3('letter-background/' . $this->background_image) : null;
    }

    public static function addModuleSetting($company)
    {
        $roles = ['employee', 'admin'];
        ModuleSetting::createRoleSettingEntry(self::MODULE_NAME, $roles, $company);
        
        // Create default letter setting for company
        self::firstOrCreate(['company_id' => $company->id]);
    }

}

