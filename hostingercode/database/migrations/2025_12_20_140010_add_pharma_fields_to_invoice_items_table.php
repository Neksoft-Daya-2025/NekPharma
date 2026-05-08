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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add pharma-specific fields to invoice_items
            if (!Schema::hasColumn('invoice_items', 'scheme')) {
                $table->string('scheme')->nullable()->after('item_summary');
            }
            if (!Schema::hasColumn('invoice_items', 'pack')) {
                $table->string('pack')->nullable()->after('scheme');
            }
            if (!Schema::hasColumn('invoice_items', 'mfr')) {
                $table->string('mfr')->nullable()->after('pack');
            }
            if (!Schema::hasColumn('invoice_items', 'batch')) {
                $table->string('batch')->nullable()->after('mfr');
            }
            if (!Schema::hasColumn('invoice_items', 'exp')) {
                $table->date('exp')->nullable()->after('batch');
            }
            if (!Schema::hasColumn('invoice_items', 'mrp')) {
                $table->decimal('mrp', 15, 2)->nullable()->after('exp');
            }
            if (!Schema::hasColumn('invoice_items', 'pts')) {
                $table->decimal('pts', 15, 2)->nullable()->after('mrp');
            }
            if (!Schema::hasColumn('invoice_items', 'ptr')) {
                $table->decimal('ptr', 15, 2)->nullable()->after('pts');
            }
            if (!Schema::hasColumn('invoice_items', 'dis')) {
                $table->decimal('dis', 15, 2)->nullable()->after('ptr');
            }
            if (!Schema::hasColumn('invoice_items', 'purchase_entry_id')) {
                $table->unsignedBigInteger('purchase_entry_id')->nullable()->after('dis');
                // Add foreign key if product_purchase_details table exists
                if (Schema::hasTable('product_purchase_details')) {
                    $table->foreign('purchase_entry_id')->references('id')->on('product_purchase_details')->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'purchase_entry_id')) {
                $table->dropForeign(['purchase_entry_id']);
                $table->dropColumn('purchase_entry_id');
            }
            if (Schema::hasColumn('invoice_items', 'dis')) {
                $table->dropColumn('dis');
            }
            if (Schema::hasColumn('invoice_items', 'ptr')) {
                $table->dropColumn('ptr');
            }
            if (Schema::hasColumn('invoice_items', 'pts')) {
                $table->dropColumn('pts');
            }
            if (Schema::hasColumn('invoice_items', 'mrp')) {
                $table->dropColumn('mrp');
            }
            if (Schema::hasColumn('invoice_items', 'exp')) {
                $table->dropColumn('exp');
            }
            if (Schema::hasColumn('invoice_items', 'batch')) {
                $table->dropColumn('batch');
            }
            if (Schema::hasColumn('invoice_items', 'mfr')) {
                $table->dropColumn('mfr');
            }
            if (Schema::hasColumn('invoice_items', 'pack')) {
                $table->dropColumn('pack');
            }
            if (Schema::hasColumn('invoice_items', 'scheme')) {
                $table->dropColumn('scheme');
            }
        });
    }
};
