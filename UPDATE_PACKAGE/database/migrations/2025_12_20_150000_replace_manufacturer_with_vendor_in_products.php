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
            // Add vendor_id column if it doesn't exist
            if (!Schema::hasColumn('products', 'vendor_id')) {
                $table->unsignedInteger('vendor_id')->nullable()->after('sub_category_id');
                
                // Add foreign key to purchase_vendors
                if (Schema::hasTable('purchase_vendors')) {
                    $table->foreign('vendor_id')->references('id')->on('purchase_vendors')->onDelete('set null');
                }
            }
        });
        
        // Migrate existing manufacturer_id data to vendor_id if manufacturers exist
        // Note: This assumes manufacturers can be mapped to vendors by name
        // You may need to adjust this logic based on your data
        if (Schema::hasColumn('products', 'manufacturer_id') && Schema::hasTable('manufacturers')) {
            // This is a placeholder - you may want to manually map manufacturers to vendors
            // or create vendors from manufacturers first
            DB::statement('
                UPDATE products p
                INNER JOIN manufacturers m ON p.manufacturer_id = m.id
                INNER JOIN purchase_vendors v ON m.name = v.primary_name OR m.name = v.company_name
                SET p.vendor_id = v.id
                WHERE p.manufacturer_id IS NOT NULL
            ');
        }
        
        // Drop manufacturer_id column and foreign key after migration
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'manufacturer_id')) {
                // Drop foreign key first
                $table->dropForeign(['manufacturer_id']);
                // Then drop column
                $table->dropColumn('manufacturer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add manufacturer_id back
            if (!Schema::hasColumn('products', 'manufacturer_id')) {
                $table->unsignedBigInteger('manufacturer_id')->nullable()->after('sub_category_id');
                
                if (Schema::hasTable('manufacturers')) {
                    $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->onDelete('set null');
                }
            }
        });
        
        // Migrate vendor_id back to manufacturer_id (reverse migration)
        if (Schema::hasColumn('products', 'vendor_id') && Schema::hasTable('manufacturers')) {
            DB::statement('
                UPDATE products p
                INNER JOIN purchase_vendors v ON p.vendor_id = v.id
                INNER JOIN manufacturers m ON v.primary_name = m.name OR v.company_name = m.name
                SET p.manufacturer_id = m.id
                WHERE p.vendor_id IS NOT NULL
            ');
        }
        
        // Drop vendor_id
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};

