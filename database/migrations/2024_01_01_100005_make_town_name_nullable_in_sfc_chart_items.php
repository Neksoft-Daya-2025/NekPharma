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
                $table->string('town_name')->nullable()->change();
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
                $table->string('town_name')->nullable(false)->change();
            });
        }
    }
};

