<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Must run after doctors exists (2025_11_01_061512) - FK to doctors.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sfc_chart_item_doctors')) {
            Schema::create('sfc_chart_item_doctors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sfc_chart_item_id');
                $table->unsignedBigInteger('doctor_id');
                $table->timestamps();

                $table->foreign('sfc_chart_item_id')->references('id')->on('sfc_chart_items')->onDelete('cascade')->onUpdate('cascade');
                $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade')->onUpdate('cascade');
                $table->unique(['sfc_chart_item_id', 'doctor_id']);
                $table->index('sfc_chart_item_id');
                $table->index('doctor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sfc_chart_item_doctors');
    }
};

