<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleSetting;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PharmaModulesSeeder extends Seeder
{
    /**
     * Run the database seeds for Pharma Field Force modules.
     *
     * @return void
     */
    public function run()
    {
        $pharmaModules = $this->getPharmaModules();
        $adminRole = Role::where('name', 'admin')->first();

        foreach ($pharmaModules as $moduleData) {
            // Create or update module
            $module = Module::updateOrCreate(
                ['module_name' => $moduleData['module_name']],
                ['description' => $moduleData['description'] ?? null]
            );

            // Create permissions for this module
            foreach ($moduleData['permissions'] as $permissionData) {
                $permissionData['module_id'] = $module->id;
                $permissionData['display_name'] = $permissionData['display_name'] ?? ucwords(str_replace('_', ' ', $permissionData['name']));

                $permission = Permission::updateOrCreate(
                    ['module_id' => $permissionData['module_id'], 'name' => $permissionData['name']],
                    $permissionData
                );

                // Assign permission to admin role with 'all' permission type (id: 4)
                if ($adminRole) {
                    PermissionRole::updateOrCreate(
                        ['permission_id' => $permission->id, 'role_id' => $adminRole->id],
                        ['permission_type_id' => 4] // 4 = 'all' permission type
                    );
                }
            }

            // Create module settings for admin and employee types
            ModuleSetting::updateOrCreate(
                ['module_name' => $moduleData['module_name'], 'type' => 'admin', 'company_id' => 1],
                ['status' => 'active']
            );

            ModuleSetting::updateOrCreate(
                ['module_name' => $moduleData['module_name'], 'type' => 'employee', 'company_id' => 1],
                ['status' => 'active']
            );
        }

        // Clear cache so modules appear immediately
        \Cache::flush();

        $this->command->info('Pharma modules seeded successfully!');
    }

    /**
     * Get pharma modules configuration
     *
     * @return array
     */
    private function getPharmaModules()
    {
        return [
            [
                'module_name' => 'doctors',
                'description' => 'Manage doctors database for pharma field force',
                'permissions' => [
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_doctors',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'view_doctors',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_doctors',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_doctors',
                    ],
                ]
            ],
            [
                'module_name' => 'chemists',
                'description' => 'Manage chemists database for pharma field force',
                'permissions' => [
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_chemists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'view_chemists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_chemists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_chemists',
                    ],
                ]
            ],
            [
                'module_name' => 'stockists',
                'description' => 'Manage stockists database for pharma field force',
                'permissions' => [
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_stockists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'view_stockists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_stockists',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_stockists',
                    ],
                ]
            ],
            [
                'module_name' => 'pharma_areas',
                'description' => 'Manage zones, regions, areas, headquarters, and stations',
                'permissions' => [
                    // Main 4 permissions (show in table columns)
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_pharma_areas',
                        'display_name' => 'Add Area Management',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'view_pharma_areas',
                        'display_name' => 'View Area Management',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_pharma_areas',
                        'display_name' => 'Edit Area Management',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_pharma_areas',
                        'display_name' => 'Delete Area Management',
                    ],
                    
                    // Component-specific permissions (show in "More" dropdown)
                    // HeadQuarters
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_headquarters',
                        'display_name' => 'Add HeadQuarters',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_headquarters',
                        'display_name' => 'View HeadQuarters',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_headquarters',
                        'display_name' => 'Edit HeadQuarters',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_headquarters',
                        'display_name' => 'Delete HeadQuarters',
                    ],
                    // Ex-Stations
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_exstations',
                        'display_name' => 'Add Ex-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_exstations',
                        'display_name' => 'View Ex-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_exstations',
                        'display_name' => 'Edit Ex-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_exstations',
                        'display_name' => 'Delete Ex-Stations',
                    ],
                    // Out-Stations
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_outstations',
                        'display_name' => 'Add Out-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_outstations',
                        'display_name' => 'View Out-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_outstations',
                        'display_name' => 'Edit Out-Stations',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_outstations',
                        'display_name' => 'Delete Out-Stations',
                    ],
                    // Areas
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_areas',
                        'display_name' => 'Add Areas',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_areas',
                        'display_name' => 'View Areas',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_areas',
                        'display_name' => 'Edit Areas',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_areas',
                        'display_name' => 'Delete Areas',
                    ],
                    // Regions
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_regions',
                        'display_name' => 'Add Regions',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_regions',
                        'display_name' => 'View Regions',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_regions',
                        'display_name' => 'Edit Regions',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_regions',
                        'display_name' => 'Delete Regions',
                    ],
                    // Zones
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'add_zones',
                        'display_name' => 'Add Zones',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'view_zones',
                        'display_name' => 'View Zones',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'edit_zones',
                        'display_name' => 'Edit Zones',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_4_ADDED_1_OWNED_2_BOTH_3_NONE_5,
                        'is_custom' => 1,
                        'name' => 'delete_zones',
                        'display_name' => 'Delete Zones',
                    ],
                    // Assignment Permissions
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'manage_area_assignments',
                        'display_name' => 'Manage Area Assignments',
                    ],
                ]
            ],
            [
                'module_name' => 'tours',
                'description' => 'Tour planning and approval for field force',
                'permissions' => [
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_tours',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'view_tours',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_tours',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_tours',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 1,
                        'name' => 'approve_tours',
                    ],
                ]
            ],
            [
                'module_name' => 'dcr_reports',
                'description' => 'Daily Call Reports (DCR) for field force activities',
                'permissions' => [
                    [
                        'allowed_permissions' => Permission::ALL_NONE,
                        'is_custom' => 0,
                        'name' => 'add_dcr_reports',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'view_dcr_reports',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'edit_dcr_reports',
                    ],
                    [
                        'allowed_permissions' => Permission::ALL_ADDED_NONE,
                        'is_custom' => 0,
                        'name' => 'delete_dcr_reports',
                    ],
                ]
            ],
        ];
    }
}

