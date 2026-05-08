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
            if (!Schema::hasColumn('product_purchase_details', 'invoice_number')) {
                $table->string('invoice_number', 100)->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchase_details', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
        });
    }
};
