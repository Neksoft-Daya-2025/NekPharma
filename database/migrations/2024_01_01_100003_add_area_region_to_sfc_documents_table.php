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
        if (Schema::hasTable('sfc_documents')) {
            Schema::table('sfc_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('sfc_documents', 'area')) {
                    $table->string('area')->nullable()->after('headquarter');
                }
                if (!Schema::hasColumn('sfc_documents', 'region')) {
                    $table->string('region')->nullable()->after('area');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sfc_documents')) {
            Schema::table('sfc_documents', function (Blueprint $table) {
                if (Schema::hasColumn('sfc_documents', 'area')) {
                    $table->dropColumn('area');
                }
                if (Schema::hasColumn('sfc_documents', 'region')) {
                    $table->dropColumn('region');
                }
            });
        }
    }
};

