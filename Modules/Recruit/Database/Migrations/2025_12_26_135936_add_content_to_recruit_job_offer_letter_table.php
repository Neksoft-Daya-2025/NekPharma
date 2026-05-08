<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruit_job_offer_letter', function (Blueprint $table) {
            if (!Schema::hasColumn('recruit_job_offer_letter', 'content')) {
                $table->longText('content')->nullable()->after('comp_amount');
            }
        });

        Schema::table('recruit_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('recruit_settings', 'offer_letter_content')) {
                $table->longText('offer_letter_content')->nullable()->after('legal_term');
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
        Schema::table('recruit_job_offer_letter', function (Blueprint $table) {
            if (Schema::hasColumn('recruit_job_offer_letter', 'content')) {
                $table->dropColumn('content');
            }
        });

        Schema::table('recruit_settings', function (Blueprint $table) {
            if (Schema::hasColumn('recruit_settings', 'offer_letter_content')) {
                $table->dropColumn('offer_letter_content');
            }
        });
    }
};
