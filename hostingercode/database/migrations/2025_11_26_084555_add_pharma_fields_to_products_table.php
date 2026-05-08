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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'manufacturer_id')) {
                $table->unsignedBigInteger('manufacturer_id')->nullable()->after('sub_category_id');
            }
            if (!Schema::hasColumn('products', 'packing')) {
                $table->string('packing')->nullable()->after('manufacturer_id');
            }
            if (!Schema::hasColumn('products', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('packing');
            }
            if (!Schema::hasColumn('products', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_number');
            }
            if (!Schema::hasColumn('products', 'ptr')) {
                $table->decimal('ptr', 15, 2)->nullable()->comment('Price to Retailer')->after('expiry_date');
            }
            if (!Schema::hasColumn('products', 'pts')) {
                $table->decimal('pts', 15, 2)->nullable()->comment('Price to Stockist')->after('ptr');
            }
        });
        
        // Add foreign key separately if column exists
        if (Schema::hasColumn('products', 'manufacturer_id')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'products' 
                AND COLUMN_NAME = 'manufacturer_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (empty($foreignKeys)) {
                Schema::table('products', function (Blueprint $table) {
                    $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropColumn(['manufacturer_id', 'packing', 'batch_number', 'expiry_date', 'ptr', 'pts']);
        });
    }
};
