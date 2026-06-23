<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PermissionRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Backfill requirement 2.1: hierarchy_level on admin, and create eight pharma roles for existing companies.
 */
class PharmaRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (!\Schema::hasColumn('roles', 'hierarchy_level')) {
            $this->command->warn('roles.hierarchy_level not found. Run migrations first.');
            return;
        }

        $pharmaRoleNames = [
            'medical-representative',
            'area-business-manager',
            'regional-business-manager',
            'zonal-business-manager',
            'sales-manager',
            'pmt',
            'hr',
        ];

        $pharmaRolesConfig = [
            ['name' => 'medical-representative', 'display_name' => 'Medical Representative', 'hierarchy_level' => 1],
            ['name' => 'area-business-manager', 'display_name' => 'Area Business Manager (ABM)', 'hierarchy_level' => 2],
            ['name' => 'regional-business-manager', 'display_name' => 'Regional Business Manager (RBM)', 'hierarchy_level' => 3],
            ['name' => 'zonal-business-manager', 'display_name' => 'Zonal Business Manager (ZBM)', 'hierarchy_level' => 4],
            ['name' => 'sales-manager', 'display_name' => 'Sales Manager', 'hierarchy_level' => 5],
            ['name' => 'pmt', 'display_name' => 'PMT', 'hierarchy_level' => 6],
            ['name' => 'hr', 'display_name' => 'HR', 'hierarchy_level' => 7],
        ];

        $roleLevelBackfill = [
            'medical-representative' => 1,
            'area-business-manager' => 2,
            'regional-manager' => 3,
            'regional-business-manager' => 3,
            'zonal-manager' => 4,
            'zonal-business-manager' => 4,
            'sales-manager' => 5,
            'pmt' => 6,
            'hr' => 7,
            'admin' => 8,
            'senior-manager-pmt' => 8,
        ];

        foreach (Company::all() as $company) {
            Role::where('company_id', $company->id)->where('name', 'admin')->update(['hierarchy_level' => 8]);

            foreach ($roleLevelBackfill as $roleName => $level) {
                Role::where('company_id', $company->id)
                    ->where('name', $roleName)
                    ->update(['hierarchy_level' => $level]);
            }

            $existingPharma = Role::where('company_id', $company->id)->whereIn('name', $pharmaRoleNames)->pluck('name')->toArray();
            $employeeRole = Role::where('company_id', $company->id)->where('name', 'employee')->first();
            if (!$employeeRole) {
                continue;
            }
            $employeePermissionRoles = PermissionRole::where('role_id', $employeeRole->id)->get();

            foreach ($pharmaRolesConfig as $pharma) {
                if (in_array($pharma['name'], $existingPharma)) {
                    continue;
                }
                $role = new Role();
                $role->name = $pharma['name'];
                $role->company_id = $company->id;
                $role->display_name = $pharma['display_name'];
                $role->description = 'Pharma role: ' . $pharma['display_name'];
                $role->hierarchy_level = $pharma['hierarchy_level'];
                $role->saveQuietly();
                foreach ($employeePermissionRoles as $pr) {
                    PermissionRole::create([
                        'permission_id' => $pr->permission_id,
                        'role_id' => $role->id,
                        'permission_type_id' => $pr->permission_type_id,
                    ]);
                }
            }
        }

        \Cache::flush();
        $this->command->info('Pharma roles and hierarchy_level backfill completed.');
    }
}
