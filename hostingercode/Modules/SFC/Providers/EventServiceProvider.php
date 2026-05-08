<?php

namespace Modules\SFC\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\SFC\Listeners\CompanyCreatedListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\NewCompanyCreatedEvent::class => [
            CompanyCreatedListener::class,
        ],
    ];
}

