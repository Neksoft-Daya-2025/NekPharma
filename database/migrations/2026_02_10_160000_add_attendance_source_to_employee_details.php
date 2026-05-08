<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS 3.1.3: Office vs Field employees.
     * Office = manual/biometric/system clock-in clock-out.
     * Field = attendance only from DCR Close Day, no manual attendance.
     */
    public function up(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->string('attendance_source', 20)->default('office')->after('reporting_to')
                ->comment('office = clock-in/out allowed; field = only DCR marks attendance');
        });
    }

    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn('attendance_source');
        });
    }
};
