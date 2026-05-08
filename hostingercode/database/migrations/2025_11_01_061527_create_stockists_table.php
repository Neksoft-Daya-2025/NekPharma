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
        Schema::create('stockists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('shopname');
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->date('dob')->nullable();
            $table->date('dom')->nullable();
            $table->string('area')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('stockist_pic')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stockists');
    }
};
