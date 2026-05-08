<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assigned HQ for CFA/Distributor (client) - requirement 3.3.3.
     */
    public function up(): void
    {
        Schema::create('client_headquarters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_detail_id');
            $table->unsignedBigInteger('headquarter_id');
            $table->timestamps();

            $table->foreign('client_detail_id')->references('id')->on('client_details')->onDelete('cascade');
            $table->foreign('headquarter_id')->references('id')->on('pharma_headquarters')->onDelete('cascade');

            $table->unique(['client_detail_id', 'headquarter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_headquarters');
    }
};
