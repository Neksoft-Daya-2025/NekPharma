<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Two-step leave approval: Manager approves first (manager_approved), then HR approves (approved).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'manager_approved_by')) {
                $table->unsignedBigInteger('manager_approved_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('leaves', 'manager_approved_at')) {
                $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'manager_approved_at')) {
                $table->dropColumn('manager_approved_at');
            }
            if (Schema::hasColumn('leaves', 'manager_approved_by')) {
                $table->dropColumn('manager_approved_by');
            }
        });
    }
};
