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
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'doctor_type')) {
            // Change enum to string to allow custom types
            DB::statement("ALTER TABLE doctors MODIFY COLUMN doctor_type VARCHAR(50) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'doctor_type')) {
            // Convert back to enum (only VIP and CORE)
            DB::statement("ALTER TABLE doctors MODIFY COLUMN doctor_type ENUM('VIP', 'CORE') NULL");
        }
    }
};

