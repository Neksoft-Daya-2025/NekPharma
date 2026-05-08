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
        // Add zone_id to pharma_regions
        Schema::table('pharma_regions', function (Blueprint $table) {
            $table->unsignedBigInteger('zone_id')->nullable()->after('company_id');
            $table->foreign('zone_id')->references('id')->on('pharma_zones')->onDelete('set null');
        });
        
        // Add region_id to pharma_areas
        Schema::table('pharma_areas', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('company_id');
            $table->foreign('region_id')->references('id')->on('pharma_regions')->onDelete('set null');
        });
        
        // Add area_id to pharma_headquarters
        Schema::table('pharma_headquarters', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->after('company_id');
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharma_regions', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
        
        Schema::table('pharma_areas', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
        
        Schema::table('pharma_headquarters', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });
    }
};
