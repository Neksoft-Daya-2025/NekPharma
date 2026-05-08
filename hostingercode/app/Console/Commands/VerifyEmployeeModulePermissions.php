<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Module;
use App\Models\ModuleSetting;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Operational check for "custom permissions vs every module" (see plan):
 * - MODULE_LIST must match modules + permissions rows (ModulePermissionSeeder).
 * - Per company, employee module_settings control which permission rows are editable in the UI.
 *
 * Exit 1 only when MODULE_LIST expects a module or permission name that is missing in the DB.
 * Extra permissions/modules in the DB (older Worksuite rows, Nwidart modules) are informational.
 */
class VerifyEmployeeModulePermissions extends Command
{
    /**
     * Employee module_settings rows for Laravel packages — not in app Module::MODULE_LIST; expected.
     *
     * @var array<int, string>
     */
    private const KNOWN_PACKAGE_MODULE_NAMES = [
        'recruit',
        'payroll',
        'letter',
        'asset',
        'zoom',
        'qrcode',
        'purchase',
    ];

    protected $signature = 'employee-modules:verify
                            {--company-id= : Limit to one company ID}
                            {--verify-roles : Assert admin and non-admin roles have a permission_role row for every permissions.id (run after add-missing-permissions)}';

    protected $description = 'Verify MODULE_LIST ↔ DB permission rows, employee module_settings coverage, and optionally permission_role completeness per role';

    public function handle(): int
    {
        $hasErrors = false;

        $moduleList = Module::MODULE_LIST;
        $definedNames = collect($moduleList)->pluck('module_name')->all();

        $this->comment('Tip: hide vendor PHP Deprecated notices: php -d error_reporting=8191 artisan employee-modules:verify');
        $this->newLine();

        $this->info('--- MODULE_LIST vs database (missing rows → run: php artisan db:seed --class=ModulePermissionSeeder --force) ---');

        foreach ($moduleList as $def) {
            $name = $def['module_name'];
            $expectedNames = collect($def['permissions'] ?? [])
                ->pluck('name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $row = Module::query()->where('module_name', $name)->first();
            if (! $row) {
                $this->error("Missing `modules` row for: {$name}");
                $hasErrors = true;

                continue;
            }

            $actualNames = Permission::query()
                ->where('module_id', $row->id)
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $missing = array_values(array_diff($expectedNames, $actualNames));
            $extraInDb = array_values(array_diff($actualNames, $expectedNames));

            if ($missing !== []) {
                $this->error("Missing permissions for [{$name}]: " . implode(', ', $missing));
                $hasErrors = true;
            }

            if ($extraInDb !== []) {
                $this->comment("  [{$name}] extra permission rows in DB (not in current MODULE_LIST): " . implode(', ', $extraInDb));
            }

            if ($missing === []) {
                $this->line("OK {$name} (" . count($actualNames) . ' permission rows)');
            }
        }

        $extras = Module::query()->whereNotIn('module_name', $definedNames)->pluck('module_name');
        foreach ($extras as $extra) {
            $this->comment("Extra `modules` row (package / legacy, OK): {$extra}");
        }

        $this->newLine();
        $this->info('--- Employee module_settings vs MODULE_LIST (per company) ---');
        $this->comment('MODULE_LIST modules not active for employees: permission UI shows those rows disabled until Settings enables the module.');

        $query = Company::query()->active();
        if ($this->option('company-id')) {
            $query->where('id', (int) $this->option('company-id'));
        }

        $companies = $query->orderBy('id')->get();
        if ($companies->isEmpty()) {
            $this->warn('No companies matched.');

            return $hasErrors ? Command::FAILURE : Command::SUCCESS;
        }

        foreach ($companies as $company) {
            $label = $company->company_name ?? '';
            $this->line("Company #{$company->id}" . ($label !== '' ? " ({$label})" : ''));

            $activeEmployee = ModuleSetting::query()
                ->where('company_id', $company->id)
                ->where('module_name', '<>', 'settings')
                ->where('status', 'active')
                ->where('type', 'employee')
                ->pluck('module_name')
                ->unique()
                ->values()
                ->all();

            $employeeModulesEffective = array_values(array_unique(array_merge($activeEmployee, ['settings', 'dashboards'])));

            foreach ($definedNames as $modName) {
                if (! in_array($modName, $employeeModulesEffective, true)) {
                    $this->comment("  … '{$modName}' not in active employee module_settings — custom permission controls stay disabled in HR until enabled.");
                }
            }

            foreach ($activeEmployee as $m) {
                if (! in_array($m, $definedNames, true)) {
                    if (in_array($m, self::KNOWN_PACKAGE_MODULE_NAMES, true)) {
                        $this->comment("  Active employee module '{$m}' — Laravel package module (not in MODULE_LIST); OK.");
                    } else {
                        $this->warn("  Active employee module '{$m}' has no MODULE_LIST entry — check spelling or legacy row.");
                    }
                }
            }
        }

        if ($this->option('verify-roles')) {
            $this->newLine();
            $this->info('--- permission_role vs permissions (every role must have a row per permission id) ---');
            $this->comment('If failures: php artisan db:seed --class=ModulePermissionSeeder --force && php artisan add-missing-permissions');
            $hasErrors = $this->verifyPermissionRoleMatrix($companies) || $hasErrors;
        }

        if ($hasErrors) {
            $this->newLine();
            $this->error('Fix missing modules/permissions on staging first, then: php artisan db:seed --class=ModulePermissionSeeder --force');
        } else {
            $this->newLine();
            $this->comment('Deploy checklist (each environment): employee-modules:verify → ModulePermissionSeeder if needed → add-missing-permissions → spot-check Settings → Roles & Permissions.');
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Exit non-zero only when a role is missing permission_role rows (not when permission types differ).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Company>  $companies
     */
    private function verifyPermissionRoleMatrix($companies): bool
    {
        $failed = false;

        $allPermissionIds = Permission::query()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($allPermissionIds === []) {
            $this->warn('No rows in `permissions` table.');

            return true;
        }

        foreach ($companies as $company) {
            $label = $company->company_name ?? '';
            $prefix = "Company #{$company->id}" . ($label !== '' ? " ({$label})" : '');

            $adminRole = Role::query()
                ->where('name', 'admin')
                ->where('company_id', $company->id)
                ->first();

            if (! $adminRole) {
                $this->error("{$prefix}: no admin role found.");
                $failed = true;

                continue;
            }

            $failed = $this->reportMissingPermissionLinksForRole($prefix, 'admin', $adminRole->id, $allPermissionIds) || $failed;

            $otherRoles = Role::query()
                ->where('name', '<>', 'admin')
                ->where('company_id', $company->id)
                ->orderBy('id')
                ->get();

            foreach ($otherRoles as $role) {
                $roleLabel = $role->display_name ?: $role->name;
                $failed = $this->reportMissingPermissionLinksForRole(
                    $prefix,
                    "non-admin ({$roleLabel})",
                    $role->id,
                    $allPermissionIds
                ) || $failed;
            }
        }

        return $failed;
    }

    /**
     * @param  array<int, int>  $allPermissionIds
     */
    private function reportMissingPermissionLinksForRole(string $companyPrefix, string $roleKind, int $roleId, array $allPermissionIds): bool
    {
        $linkedIds = DB::table('permission_role')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $missing = array_values(array_diff($allPermissionIds, $linkedIds));

        if ($missing === []) {
            $this->line("  OK {$companyPrefix} — {$roleKind}: " . count($allPermissionIds) . ' permission links');

            return false;
        }

        $names = Permission::query()->whereIn('id', $missing)->pluck('name', 'id')->all();
        $detail = [];
        foreach ($missing as $pid) {
            $detail[] = ($names[$pid] ?? "?#{$pid}");
        }

        $this->error("  {$companyPrefix} — {$roleKind} [role_id={$roleId}] missing " . count($missing) . ' permission_role row(s): ' . implode(', ', $detail));

        return true;
    }
}
