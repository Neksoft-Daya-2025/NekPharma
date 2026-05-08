<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Payment entries against supplier invoices (payment to vendor).
     */
    public function up(): void
    {
        if (Schema::hasTable('supplier_invoice_payments')) {
            return;
        }
        Schema::create('supplier_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_invoice_id');
            $table->decimal('amount', 16, 2);
            $table->date('paid_on');
            $table->string('reference', 255)->nullable()->comment('Cheque / reference no');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('supplier_invoice_id')->references('id')->on('supplier_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_payments');
    }
};
