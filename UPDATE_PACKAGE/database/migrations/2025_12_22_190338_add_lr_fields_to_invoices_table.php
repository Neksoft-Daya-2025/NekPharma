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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'lr_number')) {
                $table->string('lr_number')->nullable()->after('invoice_number');
            }
            if (!Schema::hasColumn('invoices', 'lr_date')) {
                $table->date('lr_date')->nullable()->after('lr_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'lr_date')) {
                $table->dropColumn('lr_date');
            }
            if (Schema::hasColumn('invoices', 'lr_number')) {
                $table->dropColumn('lr_number');
            }
        });
    }
};
