<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Supplier invoices (invoices of purchase entries) – distinct from CFA/Stockist sales invoices.
     */
    public function up(): void
    {
        if (Schema::hasTable('supplier_invoices')) {
            return;
        }
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->decimal('supplier_invoice_total', 16, 2)->nullable()->comment('Total as per vendor invoice document');
            $table->decimal('entry_total', 16, 2)->nullable()->comment('Computed from purchase entry lines');
            $table->string('match_status', 20)->default('draft')->comment('draft, matched, unmatched');
            $table->string('reference_number', 100)->nullable();
            $table->date('reference_date')->nullable();
            $table->string('payment_status', 20)->default('pending')->comment('pending, partial, paid');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'vendor_id', 'invoice_number', 'invoice_date'], 'sup_inv_cid_vid_invno_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
