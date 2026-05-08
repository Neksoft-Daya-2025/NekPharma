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
        Schema::create('dcr_chemist_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dcr_report_id');
            $table->unsignedBigInteger('chemist_id')->nullable();
            $table->string('chemist_name')->nullable(); // For new chemists added inline
            $table->string('chemist_mobile')->nullable();
            $table->string('chemist_email')->nullable();
            $table->string('area')->nullable();
            $table->string('station')->nullable();
            $table->integer('msl')->default(0);
            
            // RCPA 1
            $table->string('rcpa1')->nullable();
            $table->decimal('pob_amount1', 10, 2)->default(0);
            $table->string('remark1')->nullable();
            
            // RCPA 2
            $table->string('rcpa2')->nullable();
            $table->decimal('pob_amount2', 10, 2)->default(0);
            $table->string('remark2')->nullable();
            
            // RCPA 3
            $table->string('rcpa3')->nullable();
            $table->decimal('pob_amount3', 10, 2)->default(0);
            $table->string('remark3')->nullable();
            
            // RCPA 4
            $table->string('rcpa4')->nullable();
            $table->decimal('pob_amount4', 10, 2)->default(0);
            $table->string('remark4')->nullable();
            
            $table->string('input1')->nullable();
            $table->string('input2')->nullable();
            $table->string('input_remark')->nullable();
            $table->text('general_remark')->nullable();
            
            $table->timestamps();
            
            $table->foreign('dcr_report_id')->references('id')->on('dcr_reports')->onDelete('cascade');
            $table->foreign('chemist_id')->references('id')->on('chemists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dcr_chemist_visits');
    }
};
