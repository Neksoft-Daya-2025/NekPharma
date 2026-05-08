<?php

namespace Modules\Letter\Console;

use App\Models\Company;
use Illuminate\Console\Command;
use Modules\Letter\Entities\Template;
use Modules\Letter\Templates\AppointmentLetterDefaultTemplate;

class SyncAppointmentLetterTemplateCommand extends Command
{
    protected $signature = 'letter:sync-appointment-letter-template';

    protected $description = 'Update the built-in Appointment Letter template for all companies to the latest default';

    public function handle(): int
    {
        $html = AppointmentLetterDefaultTemplate::html();
        $updated = 0;
        $added = 0;

        foreach (Company::all() as $company) {
            $template = Template::where('company_id', $company->id)
                ->where('title', 'Appointment Letter')
                ->first();

            if ($template) {
                $template->description = $html;
                $template->save();
                $updated++;
            } else {
                Template::create([
                    'company_id' => $company->id,
                    'title' => 'Appointment Letter',
                    'description' => $html,
                ]);
                $added++;
            }
        }

        $this->info('Appointment Letter template synced.');
        $this->info("Updated: {$updated}");
        $this->info("Added: {$added}");

        return 0;
    }
}
