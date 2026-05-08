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
        Schema::table('expenses', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('expenses', 'expense_type')) {
                $table->string('expense_type')->default('regular')->after('category_id'); // 'regular' or 'pharma_statement'
            }
            if (!Schema::hasColumn('expenses', 'headquarter_id')) {
                $table->unsignedBigInteger('headquarter_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('expenses', 'expense_month')) {
                $table->string('expense_month')->nullable()->after('purchase_date'); // Format: YYYY-MM
            }
            if (!Schema::hasColumn('expenses', 'posted_on')) {
                $table->date('posted_on')->nullable()->after('expense_month');
            }
            if (!Schema::hasColumn('expenses', 'no_of_vouchers')) {
                $table->integer('no_of_vouchers')->default(0)->after('posted_on');
            }
            
            // Daily expense row fields
            if (!Schema::hasColumn('expenses', 'day')) {
                $table->string('day')->nullable()->after('purchase_date');
            }
            if (!Schema::hasColumn('expenses', 'town_worked')) {
                $table->string('town_worked')->nullable()->after('day');
            }
            if (!Schema::hasColumn('expenses', 'worked_with')) {
                $table->string('worked_with')->nullable()->after('town_worked');
            }
            if (!Schema::hasColumn('expenses', 'no_of_doctors_met')) {
                $table->integer('no_of_doctors_met')->default(0)->after('worked_with');
            }
            if (!Schema::hasColumn('expenses', 'no_of_retailers_met')) {
                $table->integer('no_of_retailers_met')->default(0)->after('no_of_doctors_met');
            }
            if (!Schema::hasColumn('expenses', 'headquarter_from')) {
                $table->string('headquarter_from')->nullable()->after('no_of_retailers_met');
            }
            if (!Schema::hasColumn('expenses', 'headquarter_to')) {
                $table->string('headquarter_to')->nullable()->after('headquarter_from');
            }
            if (!Schema::hasColumn('expenses', 'mode_of_transport')) {
                $table->string('mode_of_transport')->nullable()->after('headquarter_to');
            }
            if (!Schema::hasColumn('expenses', 'km')) {
                $table->decimal('km', 10, 2)->nullable()->after('mode_of_transport');
            }
            if (!Schema::hasColumn('expenses', 'fare_rs')) {
                $table->decimal('fare_rs', 10, 2)->nullable()->after('km');
            }
            if (!Schema::hasColumn('expenses', 'daily_allowance_us_rs')) {
                $table->decimal('daily_allowance_us_rs', 10, 2)->nullable()->after('fare_rs');
            }
            if (!Schema::hasColumn('expenses', 'daily_allowance_ex_rs')) {
                $table->decimal('daily_allowance_ex_rs', 10, 2)->nullable()->after('daily_allowance_us_rs');
            }
            if (!Schema::hasColumn('expenses', 'fixed_expenses')) {
                $table->decimal('fixed_expenses', 10, 2)->nullable()->after('daily_allowance_ex_rs');
            }
            if (!Schema::hasColumn('expenses', 'other_expenses')) {
                $table->decimal('other_expenses', 10, 2)->nullable()->after('fixed_expenses');
            }
        });
        
        // Add foreign key separately if headquarter_id column exists
        // Note: Foreign key will be added manually if needed, as it may cause issues during migration
        // You can add it later using: ALTER TABLE expenses ADD CONSTRAINT expenses_headquarter_id_foreign FOREIGN KEY (headquarter_id) REFERENCES pharma_headquarters(id) ON DELETE SET NULL ON UPDATE CASCADE;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'headquarter_id')) {
                try {
                    $table->dropForeign(['headquarter_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
            
            $columnsToDrop = [
                'expense_type',
                'headquarter_id',
                'expense_month',
                'posted_on',
                'no_of_vouchers',
                'day',
                'town_worked',
                'worked_with',
                'no_of_doctors_met',
                'no_of_retailers_met',
                'headquarter_from',
                'headquarter_to',
                'mode_of_transport',
                'km',
                'fare_rs',
                'daily_allowance_us_rs',
                'daily_allowance_ex_rs',
                'fixed_expenses',
                'other_expenses',
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
