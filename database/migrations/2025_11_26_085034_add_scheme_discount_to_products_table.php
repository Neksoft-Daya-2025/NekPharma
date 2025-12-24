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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'scheme')) {
                $table->decimal('scheme', 15, 2)->nullable()->after('pts');
            }
            if (!Schema::hasColumn('products', 'discount')) {
                $table->decimal('discount', 15, 2)->nullable()->after('scheme');
            }
            if (!Schema::hasColumn('products', 'total')) {
                $table->decimal('total', 15, 2)->nullable()->after('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['scheme', 'discount', 'total']);
        });
    }
};
