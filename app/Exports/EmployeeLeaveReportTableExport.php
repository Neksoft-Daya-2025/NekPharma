<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Exports the same grid as the Employee Leave Report page (per leave type, DB quota fields).
 */
class EmployeeLeaveReportTableExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Designation',
            'Department',
            'Date of joining',
            'Leave Type',
            'Per month (policy rate)',
            'Months in current cycle (same as recalc)',
            'Allotted to date',
            'Monthly cap (if set)',
            'Total Leaves Taken',
            'Remaining Leaves',
            'Over Utilized',
            'Unused Leaves',
        ];
    }
}
