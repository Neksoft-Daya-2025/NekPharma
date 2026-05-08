<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_notes', 'credit_note_type')) {
                $table->string('credit_note_type', 20)->nullable()->default('saleable')->after('note')->comment('saleable|non_saleable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            if (Schema::hasColumn('credit_notes', 'credit_note_type')) {
                $table->dropColumn('credit_note_type');
            }
        });
    }
};
