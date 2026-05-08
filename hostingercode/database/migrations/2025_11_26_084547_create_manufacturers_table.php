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
        if (!Schema::hasTable('manufacturers')) {
            Schema::create('manufacturers', function (Blueprint $table) {
                $table->id(); // bigint unsigned
                $table->unsignedInteger('company_id')->nullable();
                $table->string('name', 191); // varchar(191) not null
                $table->text('description')->nullable();
                $table->string('contact_person', 191)->nullable();
                $table->string('email', 191)->nullable();
                $table->string('phone', 191)->nullable();
                $table->text('address')->nullable();
                $table->unsignedBigInteger('added_by')->nullable();
                $table->unsignedBigInteger('last_updated_by')->nullable();
                $table->timestamps();
                
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
