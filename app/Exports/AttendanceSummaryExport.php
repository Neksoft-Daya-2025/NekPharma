<?php

namespace App\Exports;

use App\Helper\ReportCommonFields;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;

    protected $endDate;

    protected $employeeId;

    public function __construct($startDate, $endDate, $employeeId = 'all')
    {
        $this->startDate = Carbon::createFromFormat(company()->date_format, $startDate)->startOfDay();
        $this->endDate = Carbon::createFromFormat(company()->date_format, $endDate)->endOfDay();
        $this->employeeId = $employeeId;
    }

    public function headings(): array
    {
        return array_merge(
            ReportCommonFields::headings(),
            [
                __('app.workingDays'),
                'SL',
                'CL',
                'EL',
                __('modules.attendance.holiday'),
                __('app.paidDays'),
            ]
        );
    }

    public function collection()
    {
        $model = User::with(['employeeDetail', 'employeeDetail.designation', 'employeeDetail.department', 'employeeDetail.headquarter'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'employee')
            ->select('users.*');

        if ($this->employeeId !== 'all') {
            $model->where('users.id', $this->employeeId);
        }

        return $model->get();
    }

    public function map($user): array
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        $userId = $user->id;

        $presentDays = (int) Attendance::countDaysPresentByUser($startDate, $endDate, $userId);
        $halfDays = (int) Attendance::countHalfDaysByUser($startDate, $endDate, $userId);
        $workingDays = $presentDays + ($halfDays * 0.5);

        $holidaysInRange = Holiday::whereBetween(DB::raw('DATE(date)'), [$startDate->toDateString(), $endDate->toDateString()])->count();

        $leaveCounts = $this->getLeaveCountsByType($userId, $startDate, $endDate);
        $sl = $leaveCounts['SL'] ?? 0;
        $cl = $leaveCounts['CL'] ?? 0;
        $el = $leaveCounts['EL'] ?? 0;
        $paidLeaveDays = $leaveCounts['paid_days'] ?? 0;

        $paidDays = $workingDays + $paidLeaveDays;

        $employeeDetail = $user->employeeDetail;
        $common = $employeeDetail
            ? ReportCommonFields::mapEmployeeRow($employeeDetail, $user)
            : [$user->name, '-', '-', '-', '-', '-'];

        return array_merge($common, [
            round($workingDays, 1),
            $sl,
            $cl,
            $el,
            $holidaysInRange,
            round($paidDays, 1),
        ]);
    }

    /**
     * Get approved leave days in period mapped to SL, CL, EL and total paid leave days.
     */
    protected function getLeaveCountsByType(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $leaves = Leave::with('type')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('leave_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $sl = $cl = $el = $paidDays = 0;

        foreach ($leaves as $leave) {
            $days = ($leave->duration === 'half day' || $leave->half_day_type) ? 0.5 : 1;
            $typeName = $leave->type ? $leave->type->type_name : '';
            $key = $this->mapLeaveTypeToColumn($typeName);
            if ($key === 'SL') {
                $sl += $days;
            } elseif ($key === 'CL') {
                $cl += $days;
            } elseif ($key === 'EL') {
                $el += $days;
            }
            if ($leave->type && $leave->type->paid) {
                $paidDays += $days;
            }
        }

        return ['SL' => $sl, 'CL' => $cl, 'EL' => $el, 'paid_days' => $paidDays];
    }

    protected function mapLeaveTypeToColumn(string $typeName): string
    {
        $name = strtolower($typeName);
        if (str_contains($name, 'sick') || $name === 'sl') {
            return 'SL';
        }
        if (str_contains($name, 'casual') || $name === 'cl') {
            return 'CL';
        }
        if (str_contains($name, 'earned') || str_contains($name, 'annual') || $name === 'el') {
            return 'EL';
        }
        return 'Other';
    }
}
