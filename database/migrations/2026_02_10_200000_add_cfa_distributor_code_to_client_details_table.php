<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Optional display User ID for CFA/Distributor (e.g. CFA-001).
     */
    public function up(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('client_details', 'cfa_distributor_code')) {
                $table->string('cfa_distributor_code', 50)->nullable()->after('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (Schema::hasColumn('client_details', 'cfa_distributor_code')) {
                $table->dropColumn('cfa_distributor_code');
            }
        });
    }
};
