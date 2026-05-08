<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cfa_stockists')) {
            return;
        }
        Schema::table('cfa_stockists', function (Blueprint $table) {
            if (!Schema::hasColumn('cfa_stockists', 'headquarter_id')) {
                $table->unsignedBigInteger('headquarter_id')->nullable()->after('msl_number');
                $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('set null');
            }
            if (!Schema::hasColumn('cfa_stockists', 'area_id')) {
                $table->unsignedBigInteger('area_id')->nullable()->after('headquarter_id');
                $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cfa_stockists')) {
            return;
        }
        Schema::table('cfa_stockists', function (Blueprint $table) {
            if (Schema::hasColumn('cfa_stockists', 'headquarter_id')) {
                $table->dropForeign(['headquarter_id']);
                $table->dropColumn('headquarter_id');
            }
            if (Schema::hasColumn('cfa_stockists', 'area_id')) {
                $table->dropForeign(['area_id']);
                $table->dropColumn('area_id');
            }
        });
    }
};
