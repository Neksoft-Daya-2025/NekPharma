<?php

namespace Modules\SFC\Listeners;

use Modules\SFC\Entities\SFCSetting;

class CompanyCreatedListener
{
    /**
     * Handle the event.
     */
    public function handle($event)
    {
        $company = $event->company;
        SFCSetting::addModuleSetting($company);
    }
}

