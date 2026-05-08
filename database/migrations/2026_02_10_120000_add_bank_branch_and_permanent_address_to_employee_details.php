<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requirement 3.1.1: Bank branch name (bank's branch) and permanent address.
     */
    public function up(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_details', 'bank_branch_name')) {
                $table->string('bank_branch_name')->nullable()->after('aadhar_number');
            }
            if (!Schema::hasColumn('employee_details', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (Schema::hasColumn('employee_details', 'bank_branch_name')) {
                $table->dropColumn('bank_branch_name');
            }
            if (Schema::hasColumn('employee_details', 'permanent_address')) {
                $table->dropColumn('permanent_address');
            }
        });
    }
};
