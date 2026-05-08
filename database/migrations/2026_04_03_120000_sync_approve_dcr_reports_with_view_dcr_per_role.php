<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * For each role that can view DCR reports (view_dcr_reports not "none"),
 * grant approve_dcr_reports at the same permission level if missing or set to none.
 */
return new class extends Migration
{
    public function up(): void
    {
        $viewPerm = Permission::where('name', 'view_dcr_reports')->first();
        $approvePerm = Permission::where('name', 'approve_dcr_reports')->first();
        if (! $viewPerm || ! $approvePerm) {
            return;
        }

        $viewRows = DB::table('permission_role')->where('permission_id', $viewPerm->id)->get();
        foreach ($viewRows as $row) {
            if ((int) $row->permission_type_id === 5) {
                continue;
            }

            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $row->role_id, 'permission_id' => $approvePerm->id],
                ['permission_type_id' => $row->permission_type_id]
            );
        }
    }

    public function down(): void
    {
        // Intentionally left blank: cannot safely revert without knowing prior approve levels.
    }
};
