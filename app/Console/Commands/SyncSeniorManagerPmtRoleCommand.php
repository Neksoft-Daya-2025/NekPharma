<?php

namespace App\Console\Commands;

use App\Models\PermissionRole;
use App\Models\PermissionType;
use App\Models\Role;
use App\Models\User;
use App\Services\SeniorManagerPmtHrPermissionIds;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSeniorManagerPmtRoleCommand extends Command
{
    public const ROLE_NAME = 'senior-manager-pmt';

    protected $signature = 'role:sync-senior-manager-pmt
                            {--company-id= : Limit to a single company id}';

    protected $description = 'Clone admin permission_role to senior-manager-pmt, then set HR-bucket permissions to none (5). Re-sync users on that role.';

    public function handle(): int
    {
        $noneTypeId = PermissionType::query()->where('name', 'none')->value('id');
        if (! $noneTypeId) {
            $this->error('permission_types row for "none" not found.');

            return self::FAILURE;
        }

        $hrIds = SeniorManagerPmtHrPermissionIds::all();
        if (empty($hrIds)) {
            $this->warn('No HR permission ids resolved; check modules/permissions in DB.');
        } else {
            $this->info('HR deny bucket: ' . count($hrIds) . ' permission(s).');
        }

        $query = Role::query()->where('name', 'admin');
        if ($this->option('company-id')) {
            $query->where('company_id', (int) $this->option('company-id'));
        }

        $adminRoles = $query->get();
        if ($adminRoles->isEmpty()) {
            $this->error('No admin role found for the given scope.');

            return self::FAILURE;
        }

        foreach ($adminRoles as $adminRole) {
            $this->syncForCompany($adminRole, (int) $noneTypeId, $hrIds);
        }

        $this->info('Done. Mark affected users for permission re-sync: run: php artisan sync-user-permissions all');
        $this->markSmpUsersUnsynced();

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $hrIds
     */
    private function syncForCompany(Role $adminRole, int $noneTypeId, array $hrIds): void
    {
        $companyId = (int) $adminRole->company_id;

        $smp = Role::query()
            ->where('company_id', $companyId)
            ->where('name', self::ROLE_NAME)
            ->first();

        if (! $smp) {
            $smp = new Role();
            $smp->company_id = $companyId;
            $smp->name = self::ROLE_NAME;
            $smp->display_name = 'Senior Manager PMT';
            $smp->description = 'Admin-like access without HR / staff / leave / attendance / payroll / letters (managed by role:sync-senior-manager-pmt).';
            $smp->hierarchy_level = 8;
            $smp->save();
            $this->info("Created role " . self::ROLE_NAME . " for company_id={$companyId}.");
        } else {
            $smp->hierarchy_level = 8;
            $smp->save();
        }

        DB::transaction(function () use ($adminRole, $smp, $noneTypeId, $hrIds) {
            PermissionRole::query()->where('role_id', $smp->id)->delete();

            $source = PermissionRole::query()
                ->where('role_id', $adminRole->id)
                ->get();

            foreach ($source as $row) {
                PermissionRole::create([
                    'role_id' => $smp->id,
                    'permission_id' => $row->permission_id,
                    'permission_type_id' => $row->permission_type_id,
                ]);
            }

            if (! empty($hrIds)) {
                PermissionRole::query()
                    ->where('role_id', $smp->id)
                    ->whereIn('permission_id', $hrIds)
                    ->update(['permission_type_id' => $noneTypeId]);
            }
        });

        $this->info("Synced senior-manager-pmt for company_id={$companyId} (role_id={$smp->id}).");
    }

    private function markSmpUsersUnsynced(): void
    {
        $userIds = DB::table('role_user as ru')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('r.name', self::ROLE_NAME)
            ->pluck('ru.user_id');

        if ($userIds->isEmpty()) {
            $this->comment('No users currently assigned to senior-manager-pmt.');

            return;
        }

        User::query()
            ->whereIn('id', $userIds)
            ->update(['permission_sync' => 0]);

        $this->info('Marked ' . $userIds->count() . ' user(s) with permission_sync=0 (run sync-user-permissions).');
    }
}
