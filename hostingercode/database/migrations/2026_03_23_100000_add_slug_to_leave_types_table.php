<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stable leave code per company (e.g. CL, EL, SL) for probation and integrations.
     */
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('slug', 16)->nullable()->after('type_name');
        });

        $this->backfillSlugs();

        Schema::table('leave_types', function (Blueprint $table) {
            $table->unique(['company_id', 'slug'], 'leave_types_company_id_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropUnique('leave_types_company_id_slug_unique');
            $table->dropColumn('slug');
        });
    }

    private function backfillSlugs(): void
    {
        $map = [
            'Casual Leave' => 'CL',
            'Earned Leave' => 'EL',
            'Sick Leave' => 'SL',
        ];

        foreach ($map as $name => $code) {
            DB::table('leave_types')
                ->whereNull('slug')
                ->where('type_name', $name)
                ->update(['slug' => $code]);
        }
    }
};
