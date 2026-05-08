<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'irn_number')) {
                $table->string('irn_number')->nullable()->after('lr_date');
            }
            if (!Schema::hasColumn('invoices', 'eway_bill_number')) {
                $table->string('eway_bill_number')->nullable()->after('irn_number');
            }
            if (!Schema::hasColumn('invoices', 'eway_bill_date')) {
                $table->date('eway_bill_date')->nullable()->after('eway_bill_number');
            }
            if (!Schema::hasColumn('invoices', 'dispatch_through')) {
                $table->string('dispatch_through')->nullable()->after('eway_bill_date');
            }
            if (!Schema::hasColumn('invoices', 'lr_cases')) {
                $table->string('lr_cases', 64)->nullable()->after('dispatch_through');
            }
            if (!Schema::hasColumn('invoices', 'customer_order_reference')) {
                $table->string('customer_order_reference')->nullable()->after('lr_cases');
            }
            if (!Schema::hasColumn('invoices', 'tax_invoice_classification')) {
                $table->string('tax_invoice_classification')->nullable()->after('customer_order_reference');
            }
            if (!Schema::hasColumn('invoices', 'place_of_supply_override')) {
                $table->string('place_of_supply_override')->nullable()->after('tax_invoice_classification');
            }
            if (!Schema::hasColumn('invoices', 'ship_to_address_override')) {
                $table->text('ship_to_address_override')->nullable()->after('place_of_supply_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach ([
                'ship_to_address_override',
                'place_of_supply_override',
                'tax_invoice_classification',
                'customer_order_reference',
                'lr_cases',
                'dispatch_through',
                'eway_bill_date',
                'eway_bill_number',
                'irn_number',
            ] as $name) {
                if (Schema::hasColumn('invoices', $name)) {
                    $table->dropColumn($name);
                }
            }
        });
    }
};
