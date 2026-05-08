<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRS 3.1.3: Option to finalise monthly attendance then able to generate payroll for the particular month.
     */
    public function up(): void
    {
        Schema::create('attendance_month_finalisations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamp('finalised_at')->nullable();
            $table->unsignedBigInteger('finalised_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month'], 'att_final_company_year_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_month_finalisations');
    }
};
