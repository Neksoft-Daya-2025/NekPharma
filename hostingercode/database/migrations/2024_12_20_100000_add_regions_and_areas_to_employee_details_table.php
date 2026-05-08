<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_details', 'regions')) {
                $table->text('regions')->nullable();
            }
            if (!Schema::hasColumn('employee_details', 'areas')) {
                $table->text('areas')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (Schema::hasColumn('employee_details', 'regions')) {
                $table->dropColumn('regions');
            }
            if (Schema::hasColumn('employee_details', 'areas')) {
                $table->dropColumn('areas');
            }
        });
    }
};

