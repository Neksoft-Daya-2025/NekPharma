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
        Schema::create('cfa_stockists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('cfa_stockist_id')->unique()->nullable(); // Unique CFA Stockist ID
            $table->string('shopname');
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('dl_number')->nullable();
            $table->string('msl_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfa_stockists');
    }
};

