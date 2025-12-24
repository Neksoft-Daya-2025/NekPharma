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
        // Update doctors table
        Schema::table('doctors', function (Blueprint $table) {
            // Add new foreign key columns
            $table->unsignedBigInteger('area_id')->nullable()->after('area');
            $table->unsignedBigInteger('headquarter_id')->nullable()->after('area_id');
            $table->unsignedBigInteger('exstation_id')->nullable()->after('headquarter_id');
            $table->unsignedBigInteger('outstation_id')->nullable()->after('exstation_id');
            
            // Add foreign keys
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('set null');
            $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('set null');
            $table->foreign('exstation_id')->references('id')->on('pharma_exstations')->onDelete('set null');
            $table->foreign('outstation_id')->references('id')->on('pharma_outstations')->onDelete('set null');
        });

        // Update chemists table
        Schema::table('chemists', function (Blueprint $table) {
            // Add new foreign key columns
            $table->unsignedBigInteger('area_id')->nullable()->after('area');
            $table->unsignedBigInteger('headquarter_id')->nullable()->after('area_id');
            $table->unsignedBigInteger('exstation_id')->nullable()->after('headquarter_id');
            $table->unsignedBigInteger('outstation_id')->nullable()->after('exstation_id');
            
            // Add foreign keys
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('set null');
            $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('set null');
            $table->foreign('exstation_id')->references('id')->on('pharma_exstations')->onDelete('set null');
            $table->foreign('outstation_id')->references('id')->on('pharma_outstations')->onDelete('set null');
        });

        // Update stockists table
        Schema::table('stockists', function (Blueprint $table) {
            // Add new foreign key columns
            $table->unsignedBigInteger('area_id')->nullable()->after('area');
            $table->unsignedBigInteger('headquarter_id')->nullable()->after('area_id');
            $table->unsignedBigInteger('exstation_id')->nullable()->after('headquarter_id');
            $table->unsignedBigInteger('outstation_id')->nullable()->after('exstation_id');
            
            // Add foreign keys
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('set null');
            $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('set null');
            $table->foreign('exstation_id')->references('id')->on('pharma_exstations')->onDelete('set null');
            $table->foreign('outstation_id')->references('id')->on('pharma_outstations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['headquarter_id']);
            $table->dropForeign(['exstation_id']);
            $table->dropForeign(['outstation_id']);
            $table->dropColumn(['area_id', 'headquarter_id', 'exstation_id', 'outstation_id']);
        });

        Schema::table('chemists', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['headquarter_id']);
            $table->dropForeign(['exstation_id']);
            $table->dropForeign(['outstation_id']);
            $table->dropColumn(['area_id', 'headquarter_id', 'exstation_id', 'outstation_id']);
        });

        Schema::table('stockists', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['headquarter_id']);
            $table->dropForeign(['exstation_id']);
            $table->dropForeign(['outstation_id']);
            $table->dropColumn(['area_id', 'headquarter_id', 'exstation_id', 'outstation_id']);
        });
    }
};
