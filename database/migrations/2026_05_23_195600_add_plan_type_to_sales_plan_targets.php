<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales_plan_targets', 'plan_type')) {
            Schema::table('sales_plan_targets', function (Blueprint $table) {
                $table->string('plan_type', 30)->default('sales_plan')->after('company_id');
            });
        }

        DB::table('sales_plan_targets')
            ->where('plan_level', 'headquarter')
            ->whereNotNull('product_id')
            ->where('target_qty', '>', 0)
            ->update(['plan_type' => 'target_plan']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_plan_targets', 'plan_type')) {
            Schema::table('sales_plan_targets', function (Blueprint $table) {
                $table->dropColumn('plan_type');
            });
        }
    }
};
