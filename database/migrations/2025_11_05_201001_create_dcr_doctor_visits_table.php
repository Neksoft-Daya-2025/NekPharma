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
        Schema::create('dcr_doctor_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dcr_report_id');
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('doctor_name')->nullable(); // For new doctors added inline
            $table->string('doctor_mobile')->nullable();
            $table->string('doctor_email')->nullable();
            $table->string('qualification')->nullable();
            $table->string('speciality')->nullable();
            $table->string('area')->nullable();
            $table->integer('msl')->default(0);
            
            // Product 1
            $table->string('product1')->nullable();
            $table->integer('samples_unit1')->default(0);
            $table->decimal('pob1', 10, 2)->default(0);
            $table->string('remark1')->nullable();
            
            // Product 2
            $table->string('product2')->nullable();
            $table->integer('samples_unit2')->default(0);
            $table->decimal('pob2', 10, 2)->default(0);
            $table->string('remark2')->nullable();
            
            // Product 3
            $table->string('product3')->nullable();
            $table->integer('samples_unit3')->default(0);
            $table->decimal('pob3', 10, 2)->default(0);
            $table->string('remark3')->nullable();
            
            $table->string('input1')->nullable();
            $table->string('input2')->nullable();
            $table->string('pob')->nullable();
            $table->text('general_remark')->nullable();
            
            $table->timestamps();
            
            $table->foreign('dcr_report_id')->references('id')->on('dcr_reports')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dcr_doctor_visits');
    }
};
