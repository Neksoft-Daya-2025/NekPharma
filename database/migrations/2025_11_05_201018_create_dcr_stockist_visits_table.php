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
        Schema::create('dcr_stockist_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dcr_report_id');
            $table->unsignedBigInteger('stockist_id')->nullable();
            $table->string('stockist_name')->nullable(); // For new stockists added inline
            $table->string('stockist_mobile')->nullable();
            $table->string('stockist_email')->nullable();
            $table->string('area')->nullable();
            $table->string('station')->nullable();
            $table->integer('msl')->default(0);
            $table->string('pob')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_mobile')->nullable();
            $table->string('proprietor')->nullable();
            $table->string('proprietor_mobile')->nullable();
            $table->decimal('pob_amount', 10, 2)->default(0);
            $table->string('remark')->nullable();
            $table->text('general_remark')->nullable();
            
            $table->timestamps();
            
            $table->foreign('dcr_report_id')->references('id')->on('dcr_reports')->onDelete('cascade');
            $table->foreign('stockist_id')->references('id')->on('stockists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dcr_stockist_visits');
    }
};
