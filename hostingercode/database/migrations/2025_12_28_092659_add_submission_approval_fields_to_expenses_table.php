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
        Schema::table('expenses', function (Blueprint $table) {
            // Add submitted_to field (for pharma expenses submission workflow)
            if (!Schema::hasColumn('expenses', 'submitted_to')) {
                $table->unsignedBigInteger('submitted_to')->nullable()->after('approver_id');
            }
            
            // Add approved_at field (for tracking approval timestamp)
            if (!Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_to');
            }
        });
        
        // Add foreign key constraint separately to avoid issues
        if (Schema::hasColumn('expenses', 'submitted_to')) {
            try {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->foreign('submitted_to')->references('id')->on('users')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might already exist or table structure doesn't support it
                // Continue without foreign key constraint
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop foreign key first if it exists
            try {
                if (Schema::hasColumn('expenses', 'submitted_to')) {
                    $table->dropForeign(['expenses_submitted_to_foreign']);
                }
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            if (Schema::hasColumn('expenses', 'submitted_to')) {
                $table->dropColumn('submitted_to');
            }
            
            if (Schema::hasColumn('expenses', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
