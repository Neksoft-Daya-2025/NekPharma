<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\TourMonthLock;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Auto-lock next month's tour plan on the 25th of the current month.
 * Runs daily; when today's day is 25, inserts next month into tour_month_locks for each company.
 */
class TourLockNextMonth extends Command
{
    protected $signature = 'tour:lock-next-month';

    protected $description = 'On the 25th of each month, lock the next month for tour plan create/edit (non-admin)';

    public function handle(): int
    {
        $today = Carbon::today();

        if ($today->day !== 25) {
            return Command::SUCCESS;
        }

        $next = $today->copy()->addMonth();
        $year = (int) $next->year;
        $month = (int) $next->month;

        $companyIds = Company::active()->pluck('id');

        foreach ($companyIds as $companyId) {
            TourMonthLock::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'year' => $year,
                    'month' => $month,
                ]
            );
        }

        $this->info("Tour month lock: locked {$year}-{$month} for " . $companyIds->count() . " company(ies).");

        return Command::SUCCESS;
    }
}
