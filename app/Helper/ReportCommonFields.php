<?php

namespace App\Helper;

use App\Models\EmployeeDetails;
use App\Models\User;
use Carbon\Carbon;

/**
 * Standard headings and row values for common report fields on employee-based reports.
 * Use for exports and DataTables so all reports show: Employee Name, Employee ID,
 * Designation, Department, Date of Joining, HQ.
 */
class ReportCommonFields
{
    /**
     * Standard column headings for the common employee fields (for Excel/CSV exports).
     *
     * @return array<string>
     */
    public static function headings(): array
    {
        return [
            __('app.employee'),
            __('app.employeeId'),
            __('app.designation'),
            __('app.department'),
            __('app.dateOfJoining'),
            __('app.hq'),
        ];
    }

    /**
     * Map an EmployeeDetails instance to the standard values in the same order as headings().
     * Pass the detail and optionally the user for name (if not loaded on detail).
     *
     * @param  EmployeeDetails  $employeeDetail
     * @param  User|null  $user  Optional; if not provided, $employeeDetail->user is used for name
     * @param  string|null  $dateFormat  Optional; default from company setting
     * @return array<string>
     */
    public static function mapEmployeeRow(EmployeeDetails $employeeDetail, ?User $user = null, ?string $dateFormat = null): array
    {
        $user = $user ?? $employeeDetail->user;
        $format = $dateFormat ?? company()->date_format;

        return [
            $user ? $user->name : '-',
            $employeeDetail->employee_id ?? '-',
            $employeeDetail->designation->name ?? '-',
            $employeeDetail->department->team_name ?? '-',
            $employeeDetail->joining_date
                ? Carbon::parse($employeeDetail->joining_date)->format($format)
                : '-',
            $employeeDetail->headquarter->name ?? '-',
        ];
    }
}
