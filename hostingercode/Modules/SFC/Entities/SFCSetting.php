<?php

namespace Modules\SFC\Entities;

use App\Models\BaseModel;
use App\Models\ModuleSetting;

class SFCSetting extends BaseModel
{
    protected $table = 'sfc_settings';

    protected $guarded = ['id'];

    const MODULE_NAME = 'sfc';

    public static function addModuleSetting($company)
    {
        $roles = ['employee', 'admin'];
        ModuleSetting::createRoleSettingEntry(self::MODULE_NAME, $roles, $company);
    }
}

