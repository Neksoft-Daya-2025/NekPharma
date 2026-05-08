<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Link purchase entry lines to supplier invoice header.
     */
    public function up(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (!Schema::hasColumn('product_purchase_details', 'supplier_invoice_id')) {
                $table->unsignedBigInteger('supplier_invoice_id')->nullable()->after('id');
                $table->foreign('supplier_invoice_id')->references('id')->on('supplier_invoices')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchase_details', 'supplier_invoice_id')) {
                $table->dropForeign(['supplier_invoice_id']);
                $table->dropColumn('supplier_invoice_id');
            }
        });
    }
};
