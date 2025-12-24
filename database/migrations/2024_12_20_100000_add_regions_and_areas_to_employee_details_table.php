<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_details', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_details', 'regions')) {
                $table->text('regions')->nullable()->after('aadhar_number');
            }
            if (!Schema::hasColumn('employee_details', 'areas')) {
                $table->text('areas')->nullable()->after('regions');
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

