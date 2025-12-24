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
            $table->string('dl_number')->nullable()->after('employee_mobile');
            $table->string('gst_number')->nullable()->after('dl_number');
            $table->string('msl_number')->nullable()->after('gst_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stockists', function (Blueprint $table) {
            $table->dropColumn(['dl_number', 'gst_number', 'msl_number']);
        });
    }
};
