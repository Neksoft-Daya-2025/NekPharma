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
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('invoice_type', ['sgst_cgst', 'igst'])->default('sgst_cgst')->after('status');
        });
        
        // Migrate existing invoices that have the IGST marker in note field
        DB::statement("UPDATE invoices SET invoice_type = 'igst' WHERE note LIKE '%<!--IGST_INVOICE-->%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
};
