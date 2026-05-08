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
        Schema::table('dcr_reports', function (Blueprint $table) {
            // Add work_with column if it doesn't exist (for designations like Tour Plan)
            if (!Schema::hasColumn('dcr_reports', 'work_with')) {
                $table->text('work_with')->nullable()->after('work_status');
            }
            
            if (!Schema::hasColumn('dcr_reports', 'submitted_to')) {
                $table->unsignedInteger('submitted_to')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('dcr_reports', 'approved')) {
                $table->boolean('approved')->default(false)->after('submitted_to');
            }
            if (!Schema::hasColumn('dcr_reports', 'approved_by')) {
                $table->unsignedInteger('approved_by')->nullable()->after('approved');
            }
            if (!Schema::hasColumn('dcr_reports', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('dcr_reports', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'draft'])->default('pending')->after('approved_at');
            }
        });
        
        // Note: Foreign keys are skipped to avoid constraint issues
        // The application will work fine without foreign key constraints
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            // Drop columns if they exist (foreign keys may not exist)
            $columnsToDrop = ['submitted_to', 'approved', 'approved_by', 'approved_at', 'status', 'work_with'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('dcr_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
