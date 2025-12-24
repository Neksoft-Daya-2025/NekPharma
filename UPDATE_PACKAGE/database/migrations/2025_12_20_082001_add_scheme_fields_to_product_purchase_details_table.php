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
            if (!Schema::hasColumn('product_purchase_details', 'scheme_enabled')) {
                $table->boolean('scheme_enabled')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('product_purchase_details', 'total_quantity')) {
                $table->integer('total_quantity')->nullable()->after('scheme_enabled');
            }
            if (!Schema::hasColumn('product_purchase_details', 'free_quantity')) {
                $table->integer('free_quantity')->nullable()->after('total_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchase_details', 'free_quantity')) {
                $table->dropColumn('free_quantity');
            }
            if (Schema::hasColumn('product_purchase_details', 'total_quantity')) {
                $table->dropColumn('total_quantity');
            }
            if (Schema::hasColumn('product_purchase_details', 'scheme_enabled')) {
                $table->dropColumn('scheme_enabled');
            }
        });
    }
};
