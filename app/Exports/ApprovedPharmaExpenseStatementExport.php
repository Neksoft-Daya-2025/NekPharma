<?php

namespace App\Exports;

use App\Models\Expense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApprovedPharmaExpenseStatementExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private Collection $expenses
    ) {
    }

    public function array(): array
    {
        $dateFormat = companyOrGlobalSetting()->date_format;
        $timeFormat = companyOrGlobalSetting()->time_format;
        $rows = [];

        foreach ($this->expenses as $e) {
            /** @var Expense $e */
            $user = $e->user;
            $empCode = $user?->employeeDetail?->employee_id ?? '';
            $submitted = $e->submittedTo;
            $submittedDesig = $submitted?->employeeDetail?->designation?->name ?? '';
            $approver = $e->approver;
            $approvedAt = $e->approved_at;
            if ($approvedAt) {
                $approvedAt = is_string($approvedAt) ? \Carbon\Carbon::parse($approvedAt) : $approvedAt;
            }

            $workedWithRaw = $e->worked_with;
            $workedWithArr = $workedWithRaw ? json_decode($workedWithRaw, true) : null;
            $workedWithLabel = is_array($workedWithArr) && count($workedWithArr)
                ? implode(', ', $workedWithArr)
                : (!empty($workedWithRaw) && !is_array($workedWithRaw) ? (string) $workedWithRaw : '');

            $rows[] = [
                $user?->name ?? '',
                $empCode,
                $e->purchase_date?->translatedFormat($dateFormat) ?? '',
                $e->day ?? '',
                $e->town_worked ?? '',
                $workedWithLabel,
                (int) ($e->no_of_doctors_met ?? 0),
                (int) ($e->no_of_retailers_met ?? 0),
                $e->headquarter_from ?? '',
                $e->headquarter_to ?? '',
                $e->mode_of_transport ?? '',
                $e->km ?? 0,
                $e->fare_rs ?? 0,
                $e->daily_allowance_hq_rs ?? 0,
                $e->daily_allowance_ex_rs ?? 0,
                $e->daily_allowance_os_rs ?? 0,
                $e->fixed_expenses ?? 0,
                $e->other_expenses ?? 0,
                $e->remarks ?? '',
                $e->price ?? 0,
                $submitted?->name ?? '',
                $submittedDesig,
                $approver?->name ?? '',
                $approvedAt ? $approvedAt->translatedFormat($dateFormat) : '',
                $approvedAt ? $approvedAt->translatedFormat($timeFormat) : '',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee ID',
            'Date',
            'Day',
            'Town Worked',
            'Worked With',
            'No. of Doctors Met',
            'No. of Retailers Met',
            'Head Quarter From',
            'Head Quarter To',
            'Mode of Transport',
            'Km',
            'Fare (Rs)',
            'Daily Allowance HQ (Rs)',
            'Daily Allowance Ex (Rs)',
            'Daily Allowance O/S (Rs)',
            'Fixed Expenses (Rs)',
            'Other Expenses (Rs)',
            'Remarks',
            'Total (Rs)',
            'Submitted To',
            'Submitted To Designation',
            'Approved By',
            'Approved Date',
            'Approved Time',
        ];
    }

    public function title(): string
    {
        return 'Approved';
    }
}
