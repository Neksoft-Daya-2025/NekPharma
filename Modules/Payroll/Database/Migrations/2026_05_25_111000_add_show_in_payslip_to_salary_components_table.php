<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('salary_components', 'show_in_payslip')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $table->boolean('show_in_payslip')->default(true)->after('value_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('salary_components', 'show_in_payslip')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $table->dropColumn('show_in_payslip');
            });
        }
    }
};
