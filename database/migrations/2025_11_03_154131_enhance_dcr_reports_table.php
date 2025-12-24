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
        Schema::table('dcr_reports', function (Blueprint $table) {
            // Doctor fields enhancements
            $table->integer('doctor_msl')->default(0)->after('doctor_id');
            $table->string('doctor_area', 100)->nullable()->after('doctor_msl');
            $table->decimal('pob_doctor1', 10, 2)->default(0)->after('product1');
            $table->string('doctor_remark1', 200)->nullable()->after('pob_doctor1');
            $table->decimal('pob_doctor2', 10, 2)->default(0)->after('product2');
            $table->string('doctor_remark2', 200)->nullable()->after('pob_doctor2');
            $table->decimal('pob_doctor3', 10, 2)->default(0)->after('product3');
            $table->string('doctor_remark3', 200)->nullable()->after('pob_doctor3');
            $table->string('doctor_general_remark', 255)->nullable()->after('pob');
            
            // Chemist fields enhancements
            $table->integer('chemist_msl')->default(0)->after('chemist_id');
            $table->string('chemist_area', 200)->nullable()->after('chemist_msl');
            $table->decimal('chemist_pob_amount1', 10, 2)->default(0)->after('rcpa1');
            $table->string('chemist_remark1', 200)->nullable()->after('chemist_pob_amount1');
            $table->decimal('chemist_pob_amount2', 10, 2)->default(0)->after('rcpa2');
            $table->string('chemist_remark2', 200)->nullable()->after('chemist_pob_amount2');
            $table->decimal('chemist_pob_amount3', 10, 2)->default(0)->after('rcpa3');
            $table->string('chemist_remark3', 200)->nullable()->after('chemist_pob_amount3');
            $table->decimal('chemist_pob_amount4', 10, 2)->default(0)->after('rcpa4');
            $table->string('chemist_remark4', 200)->nullable()->after('chemist_pob_amount4');
            $table->string('chemist_input1', 200)->nullable()->after('chemist_remark4');
            $table->string('chemist_input2', 200)->nullable()->after('chemist_input1');
            $table->string('chemist_input_remark', 200)->nullable()->after('chemist_input2');
            $table->string('chemist_general_remark', 255)->nullable()->after('chemist_station');
            
            // Stockist fields enhancements
            $table->integer('stockist_msl')->default(0)->after('stockist_id');
            $table->string('stockist_area', 200)->nullable()->after('stockist_msl');
            $table->string('pob_stockist', 255)->nullable()->after('stockist_station');
            $table->string('contact_person', 50)->nullable()->after('pob_stockist');
            $table->string('contact_person_mobile', 15)->nullable()->after('contact_person');
            $table->string('proprietor', 50)->nullable()->after('contact_person_mobile');
            $table->string('proprietor_mobile', 15)->nullable()->after('proprietor');
            $table->decimal('stockist_pob_amount', 10, 2)->default(0)->after('proprietor_mobile');
            $table->string('stockist_remark', 255)->nullable()->after('stockist_pob_amount');
            $table->string('stockist_general_remark', 255)->nullable()->after('stockist_remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dcr_reports', function (Blueprint $table) {
            // Drop doctor fields
            $table->dropColumn([
                'doctor_msl', 'doctor_area', 'pob_doctor1', 'doctor_remark1',
                'pob_doctor2', 'doctor_remark2', 'pob_doctor3', 'doctor_remark3',
                'doctor_general_remark'
            ]);
            
            // Drop chemist fields
            $table->dropColumn([
                'chemist_msl', 'chemist_area', 'chemist_pob_amount1', 'chemist_remark1',
                'chemist_pob_amount2', 'chemist_remark2', 'chemist_pob_amount3', 'chemist_remark3',
                'chemist_pob_amount4', 'chemist_remark4', 'chemist_input1', 'chemist_input2',
                'chemist_input_remark', 'chemist_general_remark'
            ]);
            
            // Drop stockist fields
            $table->dropColumn([
                'stockist_msl', 'stockist_area', 'pob_stockist', 'contact_person',
                'contact_person_mobile', 'proprietor', 'proprietor_mobile',
                'stockist_pob_amount', 'stockist_remark', 'stockist_general_remark'
            ]);
        });
    }
};
