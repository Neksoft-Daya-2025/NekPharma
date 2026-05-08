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
        if (!Schema::hasTable('sfc_documents')) {
            Schema::create('sfc_documents', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id')->unsigned()->nullable();
                $table->string('name')->nullable(); // NAME field at top
                $table->string('headquarter')->nullable(); // HQ field
                
                // Doctor Visit Statistics (Summary at top)
                $table->integer('vip_dr_count')->default(52);
                $table->integer('core_dr_count')->default(48);
                $table->integer('total_dr_count')->default(100);
                $table->integer('vip_visits_per_month')->default(2);
                $table->integer('core_visits_per_month')->default(4);
                $table->integer('total_vip_visits_monthly')->default(104); // Calculated: vip_dr_count * vip_visits_per_month
                $table->integer('total_core_visits_monthly')->default(192); // Calculated: core_dr_count * core_visits_per_month
                $table->integer('total_visits_monthly')->default(296); // Calculated: total_vip + total_core
                
                // Approval fields
                $table->string('filled_by_name')->nullable(); // NAME field at bottom
                $table->string('abm_approval')->nullable(); // ABM'S APPROVAL
                $table->string('rbm_approval')->nullable(); // RBM APPROVAL
                $table->timestamp('abm_approved_at')->nullable();
                $table->timestamp('rbm_approved_at')->nullable();
                $table->integer('abm_approved_by')->unsigned()->nullable();
                $table->integer('rbm_approved_by')->unsigned()->nullable();
                
                $table->integer('added_by')->unsigned()->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('added_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
                $table->foreign('abm_approved_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
                $table->foreign('rbm_approved_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
                $table->index('company_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sfc_documents');
    }
};

