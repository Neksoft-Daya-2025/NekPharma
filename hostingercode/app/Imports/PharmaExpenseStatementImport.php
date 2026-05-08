<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class PharmaExpenseStatementImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return [
            [
                'id' => 'date',
                'name' => __('modules.expenses.purchaseDate'),
                'required' => 'Yes',
                'aliases' => ['Date', 'Purchase Date', 'Expense Date', 'Day Date'],
            ],
            [
                'id' => 'town_worked',
                'name' => 'Town Worked',
                'required' => 'No',
                'aliases' => ['Town', 'Town Worked'],
            ],
            [
                'id' => 'worked_with',
                'name' => 'Worked With (comma-separated)',
                'required' => 'No',
                'aliases' => ['Worked With', 'Work With'],
            ],
            [
                'id' => 'no_of_doctors_met',
                'name' => 'No. of Doctors Met',
                'required' => 'No',
                'aliases' => ['Doctors', 'No of Doctors'],
            ],
            [
                'id' => 'no_of_retailers_met',
                'name' => 'No. of Retailers Met',
                'required' => 'No',
                'aliases' => ['Retailers', 'No of Retailers'],
            ],
            [
                'id' => 'headquarter_from',
                'name' => 'Head Quarter From (station ID)',
                'required' => 'No',
                'aliases' => ['HQ From', 'Headquarter From'],
            ],
            [
                'id' => 'headquarter_to',
                'name' => 'Head Quarter To (station ID)',
                'required' => 'No',
                'aliases' => ['HQ To', 'Headquarter To'],
            ],
            [
                'id' => 'mode_of_transport',
                'name' => 'Mode of Transport',
                'required' => 'No',
                'aliases' => ['Transport', 'Mode'],
            ],
            [
                'id' => 'km',
                'name' => 'Km',
                'required' => 'No',
                'aliases' => ['KM', 'Kilometers'],
            ],
            [
                'id' => 'fare_rs',
                'name' => 'Fare Rs',
                'required' => 'No',
                'aliases' => ['Fare', 'Fare INR'],
            ],
            [
                'id' => 'daily_allowance_hq_rs',
                'name' => 'Daily Allowance HQ Rs',
                'required' => 'No',
                'aliases' => ['DA HQ', 'Allowance HQ'],
            ],
            [
                'id' => 'daily_allowance_ex_rs',
                'name' => 'Daily Allowance Ex Rs',
                'required' => 'No',
                'aliases' => ['DA Ex', 'Allowance Ex'],
            ],
            [
                'id' => 'daily_allowance_os_rs',
                'name' => 'Daily Allowance O/S Rs',
                'required' => 'No',
                'aliases' => ['DA OS', 'Allowance OS'],
            ],
            [
                'id' => 'fixed_expenses',
                'name' => 'Fixed Expenses',
                'required' => 'No',
                'aliases' => ['Fixed'],
            ],
            [
                'id' => 'other_expenses',
                'name' => 'Other Expenses',
                'required' => 'No',
                'aliases' => ['Other'],
            ],
            [
                'id' => 'remarks',
                'name' => 'Remarks',
                'required' => 'No',
                'aliases' => ['Remark', 'Notes'],
            ],
        ];
    }

    public function array(array $array): array
    {
        $this->processedData = $array;

        return $array;
    }

    public function getProcessedData()
    {
        return $this->processedData;
    }
}
