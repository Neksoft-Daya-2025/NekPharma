<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_plan_targets', 'target_qty')) {
            Schema::table('sales_plan_targets', function (Blueprint $table) {
                $table->decimal('target_qty', 15, 2)->default(0)->after('target_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_plan_targets', 'target_qty')) {
            Schema::table('sales_plan_targets', function (Blueprint $table) {
                $table->dropColumn('target_qty');
            });
        }
    }
};
