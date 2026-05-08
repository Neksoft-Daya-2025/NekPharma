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
        if (Schema::hasTable('sfc_chart_items')) {
            Schema::table('sfc_chart_items', function (Blueprint $table) {
                // Change one_way_km_actual to JSON to store multiple values
                if (Schema::hasColumn('sfc_chart_items', 'one_way_km_actual')) {
                    $table->json('one_way_km_actual')->nullable()->change();
                } else {
                    $table->json('one_way_km_actual')->nullable()->after('town_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sfc_chart_items')) {
            Schema::table('sfc_chart_items', function (Blueprint $table) {
                if (Schema::hasColumn('sfc_chart_items', 'one_way_km_actual')) {
                    $table->decimal('one_way_km_actual', 10, 2)->nullable()->change();
                }
            });
        }
    }
};

