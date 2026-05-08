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
        if (!Schema::hasTable('cfa_distributor_stockist')) {
            Schema::create('cfa_distributor_stockist', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('cfa_distributor_id'); // User ID (CFA/Distributor)
                $table->unsignedBigInteger('cfa_stockist_id'); // CFA Stockist ID
                $table->timestamps();
                
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('cfa_distributor_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('cfa_stockist_id')->references('id')->on('cfa_stockists')->onDelete('cascade');
                
                $table->unique(['cfa_distributor_id', 'cfa_stockist_id'], 'unique_cfa_distributor_stockist');
            });
        } else {
            // Table exists, check if column exists and add if missing
            Schema::table('cfa_distributor_stockist', function (Blueprint $table) {
                if (!Schema::hasColumn('cfa_distributor_stockist', 'cfa_stockist_id')) {
                    $table->unsignedBigInteger('cfa_stockist_id')->after('cfa_distributor_id');
                    $table->foreign('cfa_stockist_id')->references('id')->on('cfa_stockists')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfa_distributor_stockist');
    }
};

