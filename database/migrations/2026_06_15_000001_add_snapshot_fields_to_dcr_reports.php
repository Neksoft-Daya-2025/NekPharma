<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('dcr_reports', 'employee_name_snapshot')) {
                $table->string('employee_name_snapshot')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('dcr_reports', 'employee_designation_snapshot')) {
                $table->string('employee_designation_snapshot')->nullable()->after('employee_name_snapshot');
            }

            if (!Schema::hasColumn('dcr_reports', 'headquarter_id_snapshot')) {
                $table->unsignedBigInteger('headquarter_id_snapshot')->nullable()->after('headquarter');
            }

            if (!Schema::hasColumn('dcr_reports', 'station_type_snapshot')) {
                $table->string('station_type_snapshot')->nullable()->after('station');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            foreach (['station_type_snapshot', 'headquarter_id_snapshot', 'employee_designation_snapshot', 'employee_name_snapshot'] as $column) {
                if (Schema::hasColumn('dcr_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
