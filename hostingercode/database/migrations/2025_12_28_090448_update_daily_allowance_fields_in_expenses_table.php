<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename daily_allowance_us_rs to daily_allowance_hq_rs using raw SQL (more compatible)
        if (Schema::hasColumn('expenses', 'daily_allowance_us_rs') && !Schema::hasColumn('expenses', 'daily_allowance_hq_rs')) {
            DB::statement('ALTER TABLE expenses CHANGE daily_allowance_us_rs daily_allowance_hq_rs DECIMAL(10,2) NULL');
        }
        
        // Add daily_allowance_os_rs column if it doesn't exist
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'daily_allowance_os_rs')) {
                $table->decimal('daily_allowance_os_rs', 10, 2)->nullable()->after('daily_allowance_ex_rs');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back to daily_allowance_us_rs
        if (Schema::hasColumn('expenses', 'daily_allowance_hq_rs') && !Schema::hasColumn('expenses', 'daily_allowance_us_rs')) {
            DB::statement('ALTER TABLE expenses CHANGE daily_allowance_hq_rs daily_allowance_us_rs DECIMAL(10,2) NULL');
        }
        
        // Drop daily_allowance_os_rs column if it exists
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'daily_allowance_os_rs')) {
                $table->dropColumn('daily_allowance_os_rs');
            }
        });
    }
};
