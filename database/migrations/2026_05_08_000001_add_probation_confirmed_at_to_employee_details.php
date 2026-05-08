<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_details', 'probation_confirmed_at')) {
                $table->timestamp('probation_confirmed_at')->nullable()->after('probation_end_date')
                    ->comment('Set when HR explicitly confirms end of probation via the button');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn('probation_confirmed_at');
        });
    }
};
