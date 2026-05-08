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
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->string('mode_of_payment', 100)->nullable()->after('invoice_date');
            $table->string('reference_number', 100)->nullable()->after('mode_of_payment');
            $table->date('reference_date')->nullable()->after('reference_number');
            $table->string('dispatch_through', 255)->nullable()->after('reference_date');
            $table->string('destination', 255)->nullable()->after('dispatch_through');
            $table->text('terms_of_delivery')->nullable()->after('destination');
            $table->unsignedInteger('consignee_id')->nullable()->after('vendor_id')->comment('Ship to address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_date',
                'mode_of_payment',
                'reference_number',
                'reference_date',
                'dispatch_through',
                'destination',
                'terms_of_delivery',
                'consignee_id'
            ]);
        });
    }
};
