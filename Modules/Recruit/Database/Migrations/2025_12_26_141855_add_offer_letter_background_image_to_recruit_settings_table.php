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
        Schema::table('recruit_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('recruit_settings', 'offer_letter_background_image')) {
                $table->string('offer_letter_background_image')->nullable()->after('offer_letter_content');
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
        Schema::table('recruit_settings', function (Blueprint $table) {
            if (Schema::hasColumn('recruit_settings', 'offer_letter_background_image')) {
                $table->dropColumn('offer_letter_background_image');
            }
        });
    }
};
