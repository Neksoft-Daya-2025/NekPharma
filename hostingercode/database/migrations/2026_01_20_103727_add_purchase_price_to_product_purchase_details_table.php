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
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (!Schema::hasColumn('product_purchase_details', 'purchase_price')) {
                $table->decimal('purchase_price', 10, 2)->nullable()->after('expiry');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchase_details', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });
    }
};
