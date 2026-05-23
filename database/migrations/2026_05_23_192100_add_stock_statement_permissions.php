<?php

use App\Models\Company;
use App\Models\Module;
use App\Models\ModuleSetting;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\PermissionType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::updateOrCreate(
            ['module_name' => 'stock_statements'],
            ['description' => 'Sales stock statements for field force and CFA stockists']
        );

        $permissions = [
            ['name' => 'add_stock_statements', 'allowed_permissions' => Permission::ALL_NONE],
            ['name' => 'view_stock_statements', 'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5],
            ['name' => 'edit_stock_statements', 'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5],
            ['name' => 'delete_stock_statements', 'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permissionData) {
            $permission = Permission::updateOrCreate(
                ['name' => $permissionData['name'], 'module_id' => $module->id],
                [
                    'display_name' => ucwords(str_replace('_', ' ', $permissionData['name'])),
                    'is_custom' => 0,
                    'allowed_permissions' => $permissionData['allowed_permissions'],
                ]
            );
            $createdPermissions[$permissionData['name']] = $permission;
        }

        foreach (Company::all() as $company) {
            foreach (['admin', 'employee'] as $type) {
                ModuleSetting::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'module_name' => 'stock_statements',
                        'type' => $type,
                    ],
                    ['status' => 'active']
                );
            }

            $roles = Role::withoutGlobalScopes()->where('company_id', $company->id)->get();
            foreach ($roles as $role) {
                $roleType = $role->name === 'admin' ? PermissionType::ALL : $this->stockStatementPermissionTypeForRole($role, 'view_dcr_reports');

                foreach ($createdPermissions as $name => $permission) {
                    $permissionTypeId = $role->name === 'admin' ? PermissionType::ALL : $roleType;
                    if ($name === 'add_stock_statements' && $permissionTypeId !== PermissionType::NONE) {
                        $permissionTypeId = PermissionType::ALL;
                    }

                    PermissionRole::updateOrCreate(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        ['permission_type_id' => $permissionTypeId]
                    );
                }
            }
        }

        foreach (User::with('roles')->get() as $user) {
            $role = $user->roles->where('name', '!=', 'employee')->first() ?: $user->roles->first();
            if (! $role) {
                continue;
            }

            foreach ($createdPermissions as $permission) {
                $rolePermission = PermissionRole::where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->first();

                UserPermission::updateOrCreate(
                    ['user_id' => $user->id, 'permission_id' => $permission->id],
                    ['permission_type_id' => $rolePermission->permission_type_id ?? PermissionType::NONE]
                );
            }
        }
    }

    public function down(): void
    {
        $module = Module::where('module_name', 'stock_statements')->first();
        if (! $module) {
            return;
        }

        $permissionIds = Permission::where('module_id', $module->id)
            ->whereIn('name', ['add_stock_statements', 'view_stock_statements', 'edit_stock_statements', 'delete_stock_statements'])
            ->pluck('id');

        PermissionRole::whereIn('permission_id', $permissionIds)->delete();
        UserPermission::whereIn('permission_id', $permissionIds)->delete();
        Permission::whereIn('id', $permissionIds)->delete();
        ModuleSetting::where('module_name', 'stock_statements')->delete();
        $module->delete();
    }

    private function stockStatementPermissionTypeForRole(Role $role, string $fallbackPermissionName): int
    {
        $fallbackPermission = Permission::where('name', $fallbackPermissionName)->first();
        if (! $fallbackPermission) {
            return PermissionType::NONE;
        }

        $rolePermission = PermissionRole::where('role_id', $role->id)
            ->where('permission_id', $fallbackPermission->id)
            ->first();

        return $rolePermission->permission_type_id ?? PermissionType::NONE;
    }
};
