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
        Schema::create('client_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_detail_id');
            $table->unsignedBigInteger('area_id');
            $table->timestamps();
            
            $table->foreign('client_detail_id')->references('id')->on('client_details')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('pharma_areas')->onDelete('cascade');
            
            // Ensure unique combination
            $table->unique(['client_detail_id', 'area_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_areas');
    }
};
