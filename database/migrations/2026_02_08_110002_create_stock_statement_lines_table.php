<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_statement_id');
            $table->unsignedInteger('product_id');
            $table->decimal('opening_qty', 15, 2)->default(0);
            $table->decimal('primary_qty', 15, 2)->default(0)->comment('Auto from CFA to Stockist invoice');
            $table->decimal('secondary_qty', 15, 2)->default(0)->comment('Entered by MR');
            $table->decimal('closing_qty', 15, 2)->default(0)->comment('opening + primary + secondary');
            $table->timestamps();

            $table->foreign('stock_statement_id')->references('id')->on('stock_statements')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['stock_statement_id', 'product_id'], 'stock_statement_lines_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_statement_lines');
    }
};
