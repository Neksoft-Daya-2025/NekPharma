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
            // Add samples_unit fields for doctor products (before POB)
            $table->integer('samples_unit1')->default(0)->after('product1');
            $table->integer('samples_unit2')->default(0)->after('product2');
            $table->integer('samples_unit3')->default(0)->after('product3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            $table->dropColumn(['samples_unit1', 'samples_unit2', 'samples_unit3']);
        });
    }
};
