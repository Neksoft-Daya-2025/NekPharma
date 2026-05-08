<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dcr_reports.status was ENUM('pending','approved','rejected') without 'draft'.
     * Saving drafts silently failed or coerced values, so resume-after-refresh never found rows.
     */
    public function up(): void
    {
        if (! Schema::hasTable('dcr_reports') || ! Schema::hasColumn('dcr_reports', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE dcr_reports MODIFY COLUMN status ENUM('pending','approved','rejected','draft') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('dcr_reports') || ! Schema::hasColumn('dcr_reports', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::table('dcr_reports')->where('status', 'draft')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE dcr_reports MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
