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
        Schema::table('employee_details', function (Blueprint $table) {
            $table->string('bank_account_number')->nullable()->after('overtime_hourly_rate');
            $table->string('ifsc_code')->nullable()->after('bank_account_number');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->string('uan_number')->nullable()->after('bank_name');
            $table->string('pan_number')->nullable()->after('uan_number');
            $table->string('aadhar_number')->nullable()->after('pan_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn([
                'bank_account_number',
                'ifsc_code', 
                'bank_name',
                'uan_number',
                'pan_number',
                'aadhar_number'
            ]);
        });
    }
};
