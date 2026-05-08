<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ModuleSetting;
use Illuminate\Database\Seeder;

class PharmaModulesSeeder extends Seeder
{
    /**
     * Sync pharma module settings for all companies (like original software).
     * Modules and permissions are defined in Module::PHARMA_MODULE_LIST and
     * seeded by ModulePermissionSeeder. Admin gets all permissions via EmployeePermissionSeeder.
     * New companies get pharma module settings from CompanyObserver; this seeder
     * backfills settings for existing companies.
     *
     * @return void
     */
    public function run()
    {
        $companies = Company::select('id')->get();
        $roles = ['admin', 'employee'];

        foreach ($companies as $company) {
            foreach (ModuleSetting::PHARMA_MODULES as $moduleName) {
                foreach ($roles as $type) {
                    ModuleSetting::updateOrCreate(
                        [
                            'module_name' => $moduleName,
                            'type'        => $type,
                            'company_id'  => $company->id,
                        ],
                        ['status' => 'active']
                    );
                }
            }
        }

        \Cache::flush();
        $this->command->info('Pharma module settings synced for all companies.');
    }
}
