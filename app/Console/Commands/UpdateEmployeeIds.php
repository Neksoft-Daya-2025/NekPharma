<?php

namespace App\Console\Commands;

use App\Models\EmployeeDetails;
use Illuminate\Console\Command;

class UpdateEmployeeIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:update-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing employee IDs to the new format (RVB / 100, RVB / 101, etc.)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Updating employee IDs to new format...');

        // Get all employees ordered by ID (oldest first, so admin gets RVB / 100)
        $employees = EmployeeDetails::orderBy('id')->get();

        if ($employees->isEmpty()) {
            $this->info('No employees found.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($employees as $index => $employee) {
            $newEmployeeId = 'RVB / ' . (100 + $index);
            
            $this->line("Updating employee ID {$employee->id}: {$employee->employee_id} -> {$newEmployeeId}");
            
            $employee->employee_id = $newEmployeeId;
            $employee->save();
            
            $count++;
        }

        $this->info("Successfully updated {$count} employee ID(s).");

        return Command::SUCCESS;
    }
}


