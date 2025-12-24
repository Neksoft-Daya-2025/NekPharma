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
        Schema::create('dcr_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id'); // Employee/MR ID
            $table->date('report_date');
            
            // Doctor visit details
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('speciality')->nullable();
            $table->string('headquarter')->nullable();
            $table->string('station')->nullable();
            
            // Products promoted
            $table->string('product1')->nullable();
            $table->string('product2')->nullable();
            $table->string('product3')->nullable();
            $table->string('input1')->nullable();
            $table->string('input2')->nullable();
            $table->string('pob')->nullable(); // Point of Business
            
            // Chemist visit details
            $table->unsignedBigInteger('chemist_id')->nullable();
            $table->string('chemist_station')->nullable();
            
            // RCPA (Retail Chemist Prescription Audit)
            $table->string('rcpa1')->nullable();
            $table->string('rcpa2')->nullable();
            $table->string('rcpa3')->nullable();
            $table->string('rcpa4')->nullable();
            
            // Stockist visit details
            $table->unsignedBigInteger('stockist_id')->nullable();
            $table->string('stockist_station')->nullable();
            
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
            $table->foreign('chemist_id')->references('id')->on('chemists')->onDelete('set null');
            $table->foreign('stockist_id')->references('id')->on('stockists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dcr_reports');
    }
};
