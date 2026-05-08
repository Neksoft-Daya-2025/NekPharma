<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['dcr_doctor_visits', 'dcr_chemist_visits', 'dcr_stockist_visits'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (!Schema::hasColumn($table, 'latitude')) {
                        $t->decimal('latitude', 10, 8)->nullable();
                    }
                    if (!Schema::hasColumn($table, 'longitude')) {
                        $t->decimal('longitude', 11, 8)->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['dcr_doctor_visits', 'dcr_chemist_visits', 'dcr_stockist_visits'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    if (Schema::hasColumn($table, 'latitude')) {
                        $t->dropColumn('latitude');
                    }
                    if (Schema::hasColumn($table, 'longitude')) {
                        $t->dropColumn('longitude');
                    }
                });
            }
        }
    }
};
