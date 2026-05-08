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
        if (Schema::hasTable('cfa_distributor_stockist')) {
            // Check if cfa_stockist_id column exists
            $columns = Schema::getColumnListing('cfa_distributor_stockist');
            
            if (!in_array('cfa_stockist_id', $columns)) {
                // Check if there's a similar column with different name
                if (in_array('stockist_id', $columns)) {
                    // Rename stockist_id to cfa_stockist_id
                    Schema::table('cfa_distributor_stockist', function (Blueprint $table) {
                        $table->renameColumn('stockist_id', 'cfa_stockist_id');
                    });
                } else {
                    // Add the column if it doesn't exist
                    Schema::table('cfa_distributor_stockist', function (Blueprint $table) {
                        $table->unsignedBigInteger('cfa_stockist_id')->after('cfa_distributor_id');
                    });
                    
                    // Add foreign key constraint
                    Schema::table('cfa_distributor_stockist', function (Blueprint $table) {
                        $table->foreign('cfa_stockist_id')->references('id')->on('cfa_stockists')->onDelete('cascade');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration
    }
};

