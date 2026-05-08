<?php

namespace Modules\Letter\Console;

use App\Models\Company;
use Illuminate\Console\Command;
use Modules\Letter\Entities\Template;
use Modules\Letter\Templates\OfferLetterDefaultTemplate;

class SyncOfferLetterTemplateCommand extends Command
{
    protected $signature = 'letter:sync-offer-letter-template';

    protected $description = 'Update the built-in Offer Letter template for all companies to the latest default (Recruit-aligned layout)';

    public function handle(): int
    {
        $html = OfferLetterDefaultTemplate::html();
        $updated = 0;
        $added = 0;

        foreach (Company::all() as $company) {
            $template = Template::where('company_id', $company->id)
                ->where('title', 'Offer Letter')
                ->first();

            if ($template) {
                $template->description = $html;
                $template->save();
                $updated++;
            } else {
                Template::create([
                    'company_id' => $company->id,
                    'title' => 'Offer Letter',
                    'description' => $html,
                ]);
                $added++;
            }
        }

        $this->info('Offer Letter template synced.');
        $this->info("Updated: {$updated}");
        $this->info("Added: {$added}");

        return 0;
    }
}
