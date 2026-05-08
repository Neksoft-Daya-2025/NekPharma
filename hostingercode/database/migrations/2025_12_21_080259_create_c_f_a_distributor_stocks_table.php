<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('c_f_a_distributor_stocks')) {
            return;
        }

        Schema::create('c_f_a_distributor_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('cfa_distributor_id');
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('purchase_entry_id');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->string('batch')->nullable();
            $table->date('expiry')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('available_quantity', 15, 2)->default(0);
            $table->decimal('pts', 15, 2)->nullable();
            $table->decimal('ptr', 15, 2)->nullable();
            $table->decimal('mrp', 15, 2)->nullable();
            $table->decimal('dis', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('cfa_distributor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('purchase_entry_id')->references('id')->on('product_purchase_details')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');

            $table->index(['cfa_distributor_id', 'product_id', 'batch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('c_f_a_distributor_stocks');
    }
};

