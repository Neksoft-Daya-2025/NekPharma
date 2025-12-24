<?php

namespace Modules\SFC\Console;

use App\Models\Company;
use Illuminate\Console\Command;
use Modules\SFC\Entities\SFCSetting;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sfc:activate';

    /**
     * The console command description.
     */
    protected $description = 'Add all the module settings of SFC module';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            SFCSetting::addModuleSetting($company);
        }

        $this->info('SFC module activated for all companies.');
    }
}

