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
        Schema::table('client_details', function (Blueprint $table) {
            if (!Schema::hasColumn('client_details', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('gst_number');
            }
            if (!Schema::hasColumn('client_details', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_account_name');
            }
            if (!Schema::hasColumn('client_details', 'bank_ifsc_code')) {
                $table->string('bank_ifsc_code')->nullable()->after('bank_account_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_details', function (Blueprint $table) {
            if (Schema::hasColumn('client_details', 'bank_ifsc_code')) {
                $table->dropColumn('bank_ifsc_code');
            }
            if (Schema::hasColumn('client_details', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('client_details', 'bank_account_name')) {
                $table->dropColumn('bank_account_name');
            }
        });
    }
};
