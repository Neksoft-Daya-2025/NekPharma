<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'delivery_status')) {
                $col = $table->enum('delivery_status', ['in_transit', 'received'])->default('in_transit');
                if (Schema::hasColumn('invoices', 'lr_date')) {
                    $col->after('lr_date');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'delivery_status')) {
                $table->dropColumn('delivery_status');
            }
        });
    }
};

