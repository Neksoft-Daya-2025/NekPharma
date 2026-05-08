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
        if (Schema::hasTable('cfa_stockists')) {
            Schema::table('cfa_stockists', function (Blueprint $table) {
                if (!Schema::hasColumn('cfa_stockists', 'cfa_stockist_id')) {
                    $table->string('cfa_stockist_id')->unique()->nullable()->after('company_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cfa_stockists')) {
            Schema::table('cfa_stockists', function (Blueprint $table) {
                if (Schema::hasColumn('cfa_stockists', 'cfa_stockist_id')) {
                    $table->dropUnique(['cfa_stockist_id']);
                    $table->dropColumn('cfa_stockist_id');
                }
            });
        }
    }
};

