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
        if (!Schema::hasTable('product_purchase_details')) {
            Schema::create('product_purchase_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('vendor_id')->nullable();
                $table->integer('quantity');
                $table->integer('unit_id');
                $table->string('batch', 255)->nullable();
                $table->date('expiry')->nullable();
                $table->decimal('pts', 10, 2)->nullable();
                $table->decimal('ptr', 10, 2)->nullable();
                $table->decimal('dis', 10, 2)->nullable();
                $table->decimal('mrp', 10, 2);
                $table->decimal('discount', 10, 2)->nullable();
                $table->string('discount_type', 50)->nullable();
                $table->decimal('total', 10, 2);
                $table->longText('tax')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('created_by');
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        } else {
            // Table exists, check if we need to add missing columns
            Schema::table('product_purchase_details', function (Blueprint $table) {
                if (!Schema::hasColumn('product_purchase_details', 'unit_id')) {
                    $table->integer('unit_id')->after('quantity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_purchase_details');
    }
};

