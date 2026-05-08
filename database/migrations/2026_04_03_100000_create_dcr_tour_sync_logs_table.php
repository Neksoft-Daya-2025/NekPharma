<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dcr_tour_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->date('report_date');
            $table->unsignedBigInteger('dcr_report_id')->nullable();
            $table->unsignedBigInteger('tour_id')->nullable();
            $table->string('action', 64);
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'report_date']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dcr_tour_sync_logs');
    }
};
