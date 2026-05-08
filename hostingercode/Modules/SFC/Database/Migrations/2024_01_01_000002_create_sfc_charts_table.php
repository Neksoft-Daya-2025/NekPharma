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
        if (!Schema::hasTable('sfc_charts')) {
            Schema::create('sfc_charts', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id')->unsigned()->nullable();
                $table->string('territory_name')->nullable();
                $table->string('headquarter')->nullable();
                $table->string('covered_from')->nullable();
                $table->string('town_name');
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
                $table->integer('added_by')->unsigned()->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('added_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
                $table->index('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sfc_charts');
    }
};

