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
        Schema::table('purchase_items', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('purchase_items', 'pp_without_discount')) {
                $table->double('pp_without_discount', 16, 2)->default(0)->after('unit_price');
            }
            
            if (!Schema::hasColumn('purchase_items', 'discount_percent')) {
                $table->double('discount_percent', 8, 2)->default(0)->after('pp_without_discount');
            }
            
            if (!Schema::hasColumn('purchase_items', 'purchase_price')) {
                $table->double('purchase_price', 16, 2)->nullable()->after('discount_percent');
            }
            
            if (!Schema::hasColumn('purchase_items', 'purchase_price_inc_tax')) {
                $table->double('purchase_price_inc_tax', 16, 2)->nullable()->after('purchase_price');
            }
            
            if (!Schema::hasColumn('purchase_items', 'lot_number')) {
                $table->string('lot_number')->nullable()->after('purchase_price_inc_tax');
            }
            
            if (!Schema::hasColumn('purchase_items', 'mfg_date')) {
                $table->date('mfg_date')->nullable()->after('lot_number');
            }
            
            if (!Schema::hasColumn('purchase_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('mfg_date');
            }
            
            if (!Schema::hasColumn('purchase_items', 'purchase_line_tax_id')) {
                $table->unsignedInteger('purchase_line_tax_id')->nullable()->after('expiry_date');
            }
        });
        
        // Check and fix column type if needed, then add foreign key
        if (Schema::hasColumn('purchase_items', 'purchase_line_tax_id')) {
            // Get current column type
            $columnInfo = DB::selectOne("
                SELECT COLUMN_TYPE, DATA_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'purchase_items' 
                AND COLUMN_NAME = 'purchase_line_tax_id'
            ");
            
            // If column exists but is bigint, modify it to int to match taxes.id
            if ($columnInfo && (strpos(strtolower($columnInfo->COLUMN_TYPE), 'bigint') !== false || strtolower($columnInfo->DATA_TYPE) === 'bigint')) {
                DB::statement('ALTER TABLE purchase_items MODIFY COLUMN purchase_line_tax_id INT(10) UNSIGNED NULL');
            }
            
            // Check if foreign key doesn't already exist
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'purchase_items' 
                AND COLUMN_NAME = 'purchase_line_tax_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($foreignKeys)) {
                // Verify taxes table exists and has correct id type
                $taxesTable = DB::selectOne("
                    SELECT COLUMN_TYPE, DATA_TYPE 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'taxes' 
                    AND COLUMN_NAME = 'id'
                ");
                
                if ($taxesTable) {
                    try {
                        Schema::table('purchase_items', function (Blueprint $table) {
                            $table->foreign('purchase_line_tax_id')->references('id')->on('taxes')->onDelete('set null');
                        });
                    } catch (\Exception $e) {
                        // If foreign key creation fails, skip it
                        // The column exists and can be used without foreign key constraint
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('purchase_items', 'purchase_line_tax_id')) {
                $table->dropForeign(['purchase_line_tax_id']);
            }
            
            // Drop columns
            $columns = [
                'pp_without_discount',
                'discount_percent',
                'purchase_price',
                'purchase_price_inc_tax',
                'lot_number',
                'mfg_date',
                'expiry_date',
                'purchase_line_tax_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchase_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

