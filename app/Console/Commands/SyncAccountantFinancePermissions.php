<?php

namespace App\Console\Commands;

use App\Models\PermissionRole;
use Illuminate\Console\Command;

/**
 * Applies finance-focused "all" permission types to the accountant role (per company),
 * then re-syncs user_permissions for users without customised permissions.
 */
class SyncAccountantFinancePermissions extends Command
{
    protected $signature = 'roles:sync-accountant-finance {--company-id= : Limit to one company ID}';

    protected $description = 'Set accountant role finance-related permissions to "all" and sync default user permissions';

    public function handle(): int
    {
        $companyId = $this->option('company-id');
        $cid = $companyId !== null && $companyId !== '' ? (int) $companyId : null;

        $names = PermissionRole::accountantFinancePermissionNames();
        $this->info('Finance permission names to apply (all): ' . count($names));

        $updatedRoles = PermissionRole::syncAccountantRoleFinancePermissions($cid);

        if ($updatedRoles === 0) {
            $this->warn('No accountant role found' . ($cid !== null ? " for company_id={$cid}" : '') . '.');

            return self::SUCCESS;
        }

        $this->info("Updated accountant role(s): {$updatedRoles}");

        return self::SUCCESS;
    }
}
