<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('client_details', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('gst_number');
            }
            if (!Schema::hasColumn('client_details', 'dl_number')) {
                $table->string('dl_number')->nullable()->after('region_id');
            }
        });
        
        // Add foreign key separately if column exists
        if (Schema::hasColumn('client_details', 'region_id')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'client_details' 
                AND COLUMN_NAME = 'region_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($foreignKeys)) {
                Schema::table('client_details', function (Blueprint $table) {
                    $table->foreign('region_id')->references('id')->on('pharma_regions')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn(['region_id', 'dl_number']);
        });
    }
};
