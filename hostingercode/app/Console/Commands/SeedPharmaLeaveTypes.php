<?php

namespace App\Console\Commands;

use App\Models\Company;
use Database\Seeders\PharmaLeaveTypeSeeder;
use Illuminate\Console\Command;

class SeedPharmaLeaveTypes extends Command
{
    protected $signature = 'app:seed-pharma-leave-types';

    protected $description = 'Seed default pharma leave types (CL, EL, SL) for all companies';

    public function handle()
    {
        $companies = Company::pluck('id');
        $seeder = new PharmaLeaveTypeSeeder();

        foreach ($companies as $companyId) {
            $seeder->run($companyId);
            $this->info("Seeded leave types for company ID: {$companyId}");
        }

        $this->info('Done.');
        return 0;
    }
}
