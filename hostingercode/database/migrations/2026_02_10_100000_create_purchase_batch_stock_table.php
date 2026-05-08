<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Batch-wise inventory for purchase stock (SRS 3.3.2).
     */
    public function up(): void
    {
        if (Schema::hasTable('purchase_batch_stock')) {
            return;
        }

        Schema::create('purchase_batch_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->string('batch', 255)->nullable();
            $table->date('expiry')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'product_id', 'batch', 'expiry']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_batch_stock');
    }
};
