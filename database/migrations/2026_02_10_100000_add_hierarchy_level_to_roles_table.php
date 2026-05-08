<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds hierarchy_level for requirement 2.1 (1 = MR bottom, 8 = Admin top).
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'hierarchy_level')) {
                $table->unsignedTinyInteger('hierarchy_level')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'hierarchy_level')) {
                $table->dropColumn('hierarchy_level');
            }
        });
    }
};
