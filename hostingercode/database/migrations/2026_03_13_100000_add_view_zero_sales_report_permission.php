<?php

use App\Models\Company;
use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $module = Module::where('module_name', 'reports')->first();

        if (!is_null($module)) {
            $permissionName = 'view_zero_sales_report';

            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'module_id' => $module->id,
            ], [
                'display_name' => 'View Zero Sales Report',
                'is_custom' => 1,
                'allowed_permissions' => Permission::ALL_NONE,
            ]);

            $companies = Company::select('id')->get();

            foreach ($companies as $company) {
                $role = Role::where('name', 'admin')
                    ->where('company_id', $company->id)
                    ->first();

                if ($role) {
                    $permissionRole = PermissionRole::where('permission_id', $permission->id)->where('role_id', $role->id)->first() ?: new PermissionRole();
                    $permissionRole->permission_id = $permission->id;
                    $permissionRole->role_id = $role->id;
                    $permissionRole->permission_type_id = 4; // All
                    $permissionRole->save();
                }
            }

            $adminUser = User::allAdmins();

            foreach ($adminUser as $adminUsers) {
                $userPermission = UserPermission::where('permission_id', $permission->id)->where('user_id', $adminUsers->id)->first() ?: new UserPermission();
                $userPermission->user_id = $adminUsers->id;
                $userPermission->permission_id = $permission->id;
                $userPermission->permission_type_id = 4; // All
                $userPermission->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'view_zero_sales_report')->first();
        if ($permission) {
            PermissionRole::where('permission_id', $permission->id)->delete();
            UserPermission::where('permission_id', $permission->id)->delete();
            $permission->delete();
        }
    }
};
