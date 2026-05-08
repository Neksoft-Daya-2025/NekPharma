<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_statements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id')->comment('MR who submitted');
            $table->unsignedBigInteger('cfa_stockist_id');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('status', 20)->default('draft')->comment('draft|submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cfa_stockist_id')->references('id')->on('cfa_stockists')->onDelete('cascade');
            $table->unique(['company_id', 'user_id', 'cfa_stockist_id', 'period_year', 'period_month'], 'stock_statements_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_statements');
    }
};
