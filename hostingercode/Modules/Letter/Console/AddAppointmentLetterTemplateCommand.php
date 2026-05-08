<?php

namespace Modules\Letter\Console;

use Illuminate\Console\Command;
use Modules\Letter\Entities\Template;
use App\Models\Company;
use Modules\Letter\Templates\AppointmentLetterDefaultTemplate;

class AddAppointmentLetterTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'letter:add-appointment-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Appointment Letter template to all companies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appointmentLetter = AppointmentLetterDefaultTemplate::html();


        $companies = Company::all();
        $added = 0;
        $updated = 0;

        foreach ($companies as $company) {
            $existingTemplate = Template::where('company_id', $company->id)
                ->where('title', 'Appointment Letter')
                ->first();

            if ($existingTemplate) {
                $existingTemplate->description = $appointmentLetter;
                $existingTemplate->save();
                $updated++;
            } else {
                Template::create([
                    'company_id' => $company->id,
                    'title' => 'Appointment Letter',
                    'description' => $appointmentLetter,
                ]);
                $added++;
            }
        }

        $this->info("Appointment Letter template added successfully!");
        $this->info("Added: {$added} templates");
        $this->info("Updated: {$updated} templates");

        return 0;
    }
}

