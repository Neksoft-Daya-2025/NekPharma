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
        Schema::table('letter_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('letter_settings', 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            }
            if (!Schema::hasColumn('letter_settings', 'background_image')) {
                $table->string('background_image')->nullable()->after('notify_update');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letter_settings', function (Blueprint $table) {
            if (Schema::hasColumn('letter_settings', 'background_image')) {
                $table->dropColumn('background_image');
            }
            if (Schema::hasColumn('letter_settings', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};

