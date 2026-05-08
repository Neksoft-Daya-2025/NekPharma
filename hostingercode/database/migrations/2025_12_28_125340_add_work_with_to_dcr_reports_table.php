<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            // Add work_with column if it doesn't exist (for designations like Tour Plan)
            if (!Schema::hasColumn('dcr_reports', 'work_with')) {
                $table->text('work_with')->nullable()->after('work_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            if (Schema::hasColumn('dcr_reports', 'work_with')) {
                $table->dropColumn('work_with');
            }
        });
    }
};
