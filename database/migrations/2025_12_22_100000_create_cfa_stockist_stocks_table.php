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
        // Check if table already exists before creating
        if (Schema::hasTable('cfa_stockist_stocks')) {
            return;
        }
        
        Schema::create('cfa_stockist_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('cfa_distributor_id'); // User ID of CFA/Distributor (who is billing) - matches users.id type
            $table->unsignedBigInteger('cfa_stockist_id'); // CFA Stockist ID (who is being billed)
            $table->unsignedInteger('product_id');
            $table->unsignedBigInteger('cfa_distributor_stock_id'); // Reference to CFADistributorStock
            $table->unsignedInteger('invoice_id')->nullable(); // Reference to Invoice that created this stock
            $table->string('batch')->nullable();
            $table->date('expiry')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('pts', 15, 2)->nullable();
            $table->decimal('ptr', 15, 2)->nullable();
            $table->decimal('mrp', 15, 2)->nullable();
            $table->decimal('dis', 15, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('cfa_distributor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cfa_stockist_id')->references('id')->on('cfa_stockists')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('cfa_distributor_stock_id')->references('id')->on('c_f_a_distributor_stocks')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            
            $table->index(['cfa_distributor_id', 'cfa_stockist_id', 'product_id', 'batch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfa_stockist_stocks');
    }
};

