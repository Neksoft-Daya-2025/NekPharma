<?php

namespace App\Console\Commands;

use App\Helper\DeployNotice;
use Illuminate\Console\Command;

class PublishDeployNoticeCommand extends Command
{
    protected $signature = 'deploy:notice {message? : Toast message shown on dashboard after deploy}';

    protected $description = 'Write deploy_notice.json so users see a one-time dashboard toast after an update';

    public function handle(): int
    {
        $message = $this->argument('message')
            ?: 'Ryva CRM was updated successfully. Doctor import and employee fixes are live.';

        DeployNotice::publish($message);

        $this->info('Deploy notice saved: ' . DeployNotice::path());
        $this->info($message);

        return self::SUCCESS;
    }
}
