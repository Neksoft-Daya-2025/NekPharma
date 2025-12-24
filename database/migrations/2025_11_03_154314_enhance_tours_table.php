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
        Schema::table('tours', function (Blueprint $table) {
            // Add day of week
            $table->string('day', 15)->nullable()->after('date');
            
            // Change work_with to TEXT to support multiple employees (comma-separated)
            // Note: In MySQL, we need to drop and recreate foreign key
        });
        
        // Drop foreign key temporarily
        Schema::table('tours', function (Blueprint $table) {
            $table->dropForeign(['work_with']);
        });
        
        // Modify column type
        Schema::table('tours', function (Blueprint $table) {
            $table->text('work_with')->nullable()->change();
        });
        
        // Change station to TEXT for multiple stations
        Schema::table('tours', function (Blueprint $table) {
            $table->text('station')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('day');
            
            // Revert work_with back to integer
            $table->unsignedInteger('work_with')->nullable()->change();
            
            // Revert station back to string
            $table->string('station')->nullable()->change();
            
            // Re-add foreign key
            $table->foreign('work_with')->references('id')->on('users')->onDelete('set null');
        });
    }
};
