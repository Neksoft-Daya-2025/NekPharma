<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_plan_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('plan_level', 20)->comment('headquarter|area|region');
            $table->unsignedBigInteger('headquarter_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->unsignedInteger('product_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('cascade');
            $table->foreign('region_id')->references('id')->on('pharma_regions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_plan_targets');
    }
};
