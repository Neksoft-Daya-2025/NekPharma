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
        if (!Schema::hasTable('sfc_chart_items')) {
            Schema::create('sfc_chart_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sfc_document_id');
                $table->integer('serial_number')->default(1); // SL column
                $table->string('covered_from')->nullable();
                $table->string('town_name')->nullable(); // NAME OF THE TOWN TO BE COVERED
                $table->decimal('one_way_km_actual', 10, 2)->nullable();
                $table->decimal('grace', 10, 2)->nullable();
                $table->decimal('total_km', 10, 2)->nullable();
                $table->decimal('two_way_fare', 10, 2)->nullable();
                $table->decimal('one_way_fare', 10, 2)->nullable();
                $table->string('ex_hq_os')->nullable();
                $table->string('mode_of_travel')->nullable();
                $table->decimal('time_in_hours', 5, 2)->nullable();
                $table->integer('no_of_days_monthly')->nullable();
                $table->integer('vip_dr_count')->default(0);
                $table->integer('core_dr_count')->default(0);
                $table->integer('total_dr_count')->default(0);
                $table->string('stockist_name')->nullable();
                $table->decimal('current_business', 15, 2)->nullable();
                $table->decimal('approx_business_expected', 15, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('sfc_document_id')->references('id')->on('sfc_documents')->onDelete('cascade')->onUpdate('cascade');
                $table->index('sfc_document_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sfc_chart_items');
    }
};

