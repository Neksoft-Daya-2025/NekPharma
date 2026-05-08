<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Letter\Entities\LetterSetting;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // License validation removed - module is always activated
        // \App\Models\Module::validateVersion(LetterSetting::MODULE_NAME);

        if (!Schema::hasTable('letter_settings')) {
            Schema::create('letter_settings', function (Blueprint $table) {
                $table->id();
                // License fields removed - module is permanently activated
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });

            LetterSetting::create([]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('letter_settings');
    }

};
