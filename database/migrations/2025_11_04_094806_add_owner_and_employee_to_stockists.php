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
        Schema::table('stockists', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('address');
            $table->string('owner_mobile')->nullable()->after('owner_name');
            $table->string('employee_name')->nullable()->after('owner_mobile');
            $table->string('employee_mobile')->nullable()->after('employee_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockists', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'owner_mobile', 'employee_name', 'employee_mobile']);
        });
    }
};
