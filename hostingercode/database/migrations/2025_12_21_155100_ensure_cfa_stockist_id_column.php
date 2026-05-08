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
            // Check if cfa_stockist_id column exists using raw SQL
            $columns = DB::select("SHOW COLUMNS FROM `cfa_distributor_stockist`");
            $columnNames = array_column($columns, 'Field');
            
            if (!in_array('cfa_stockist_id', $columnNames)) {
                // Check if stockist_id exists (different name)
                if (in_array('stockist_id', $columnNames)) {
                    // Rename using raw SQL (more compatible)
                    DB::statement("ALTER TABLE `cfa_distributor_stockist` CHANGE `stockist_id` `cfa_stockist_id` BIGINT UNSIGNED NOT NULL");
                } else {
                    // Add the column
                    DB::statement("ALTER TABLE `cfa_distributor_stockist` ADD COLUMN `cfa_stockist_id` BIGINT UNSIGNED NOT NULL AFTER `cfa_distributor_id`");
                }
                
                // Add foreign key if it doesn't exist
                try {
                    DB::statement("ALTER TABLE `cfa_distributor_stockist` ADD CONSTRAINT `cfa_distributor_stockist_cfa_stockist_id_foreign` FOREIGN KEY (`cfa_stockist_id`) REFERENCES `cfa_stockists` (`id`) ON DELETE CASCADE");
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};

