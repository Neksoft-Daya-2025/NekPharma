<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Collection;

/**
 * Permission ids that must be "none" for the senior-manager-pmt role (no HR / staff / payroll / letters).
 */
class SeniorManagerPmtHrPermissionIds
{
    /**
     * @return list<int>
     */
    public static function all(): array
    {
        return self::collectIds()->values()->all();
    }

    public static function collectIds(): Collection
    {
        $moduleNames = [
            'employees',
            'leaves',
            'attendance',
            'payroll',
            'letter',
            'recruit',
        ];

        $moduleIds = Module::query()
            ->whereIn('module_name', $moduleNames)
            ->pluck('id');

        $ids = Permission::query()
            ->whereIn('module_id', $moduleIds)
            ->pluck('id');

        $ids = $ids->merge(
            Permission::query()->where('name', 'view_hr_dashboard')->pluck('id')
        );

        $ids = $ids->merge(
            Permission::query()->where('name', 'like', '%payroll%')->pluck('id')
        );

        $ids = $ids->merge(
            Permission::query()->where('name', 'like', '%offer_letter%')->pluck('id')
        );

        $ids = $ids->merge(
            Permission::query()
                ->whereIn('name', [
                    'add_letter',
                    'edit_letter',
                    'delete_letter',
                    'view_letter',
                ])
                ->pluck('id')
        );

        return $ids->unique()->values();
    }
}
