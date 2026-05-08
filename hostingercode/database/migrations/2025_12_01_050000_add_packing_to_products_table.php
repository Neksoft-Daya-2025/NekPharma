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
        Schema::table('products', function (Blueprint $table) {
            // Add manufacturer_id if missing (required for imports)
            if (!Schema::hasColumn('products', 'manufacturer_id')) {
                $table->unsignedBigInteger('manufacturer_id')->nullable()->after('sub_category_id');
                // Add foreign key if manufacturers table exists
                if (Schema::hasTable('manufacturers')) {
                    $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->onDelete('set null');
                }
            }
            
            // Add packing if missing
            if (!Schema::hasColumn('products', 'packing')) {
                $table->string('packing')->nullable()->after('name');
            }
            
            // Add total if missing
            if (!Schema::hasColumn('products', 'total')) {
                $table->string('total')->nullable()->after('packing');
            }
            
            // Add other pharma fields if missing (from original migration)
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
            
            // Add scheme column (as string per later migration)
            if (!Schema::hasColumn('products', 'scheme')) {
                $table->string('scheme')->nullable()->after('pts');
            }
            
            // Add discount column
            if (!Schema::hasColumn('products', 'discount')) {
                $table->decimal('discount', 15, 2)->nullable()->after('scheme');
            }
            
            // Add discount_type column
            if (!Schema::hasColumn('products', 'discount_type')) {
                $table->enum('discount_type', ['flat', 'percentage'])->default('flat')->after('discount');
            }
            
            // Note: total is already added above as string after packing (user added manually)
            // If it doesn't exist yet, it will be added above
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key first if it exists
            if (Schema::hasColumn('products', 'manufacturer_id')) {
                try {
                    $table->dropForeign(['manufacturer_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
            }
            
            // Drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'manufacturer_id')) {
                $columnsToDrop[] = 'manufacturer_id';
            }
            if (Schema::hasColumn('products', 'packing')) {
                $columnsToDrop[] = 'packing';
            }
            if (Schema::hasColumn('products', 'total')) {
                $columnsToDrop[] = 'total';
            }
            if (Schema::hasColumn('products', 'batch_number')) {
                $columnsToDrop[] = 'batch_number';
            }
            if (Schema::hasColumn('products', 'expiry_date')) {
                $columnsToDrop[] = 'expiry_date';
            }
            if (Schema::hasColumn('products', 'ptr')) {
                $columnsToDrop[] = 'ptr';
            }
            if (Schema::hasColumn('products', 'pts')) {
                $columnsToDrop[] = 'pts';
            }
            if (Schema::hasColumn('products', 'scheme')) {
                $columnsToDrop[] = 'scheme';
            }
            if (Schema::hasColumn('products', 'discount')) {
                $columnsToDrop[] = 'discount';
            }
            if (Schema::hasColumn('products', 'discount_type')) {
                $columnsToDrop[] = 'discount_type';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

