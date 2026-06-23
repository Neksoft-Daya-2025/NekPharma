<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceSheetFormatExport implements FromCollection, WithHeadings, WithMapping
{
    protected Carbon $startDate;

    protected Carbon $endDate;

    protected string $employeeId;

    protected string $department;

    protected string $designation;

    protected CarbonPeriod $period;

    protected array $holidayDates = [];

    protected array $officeOpenDays = [];

    public function __construct(string $startDate, string $endDate, string $employeeId = 'all', string $department = 'all', string $designation = 'all')
    {
        $this->startDate = Carbon::createFromFormat(company()->date_format, $startDate)->startOfDay();
        $this->endDate = Carbon::createFromFormat(company()->date_format, $endDate)->endOfDay();
        $this->employeeId = $employeeId;
        $this->department = $department;
        $this->designation = $designation;
        $this->period = CarbonPeriod::create($this->startDate, $this->endDate);

        $this->holidayDates = Holiday::where('company_id', company()->id)
            ->whereBetween(DB::raw('DATE(date)'), [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->toArray();

        $shift = attendance_setting()->shift ?? EmployeeShift::where('company_id', company()->id)->first();
        if ($shift && $shift->office_open_days) {
            $days = json_decode($shift->office_open_days, true);
            $this->officeOpenDays = is_array($days) ? array_map('intval', $days) : [1, 2, 3, 4, 5];
        } else {
            $this->officeOpenDays = [1, 2, 3, 4, 5];
        }
    }

    public function headings(): array
    {
        $headers = ['Sr. No.', 'Employee ID', 'Employee Name', 'Designation', 'Department', 'HQ', 'DOJ'];

        foreach ($this->period as $date) {
            $headers[] = $date->format('d') . '-' . strtoupper($date->format('M'));
        }

        $headers = array_merge($headers, [
            'Standard Days',
            'Working Days',
            'WO',
            'Holiday',
            'SL',
            'EL',
            'CL',
            'LWP',
            'HF',
            'Total Leaves',
            'Paid Days',
        ]);

        return $headers;
    }

    public function collection(): Collection
    {
        $model = User::with(['employeeDetail', 'employeeDetail.designation', 'employeeDetail.department', 'employeeDetail.headquarter'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.name', 'employee')
            ->where('users.company_id', company()->id)
            ->select('users.*');

        if ($this->employeeId !== 'all') {
            $model->where('users.id', $this->employeeId);
        }

        if ($this->department !== 'all') {
            $model->whereHas('employeeDetail', fn ($query) => $query->where('department_id', $this->department));
        }

        if ($this->designation !== 'all') {
            $model->whereHas('employeeDetail', fn ($query) => $query->where('designation_id', $this->designation));
        }

        return $model->orderBy('users.name')->get();
    }

    public function map($user): array
    {
        static $srNo = 0;
        $srNo++;

        $ed = $user->employeeDetail;
        $empId = $ed->employee_id ?? '-';
        $empName = $user->name ?? '-';
        $designation = $ed && $ed->designation ? $ed->designation->name : '-';
        $department = $ed && $ed->department ? $ed->department->team_name : '-';
        $hq = $ed && $ed->headquarter ? $ed->headquarter->name : '-';
        $doj = $ed && $ed->joining_date
            ? Carbon::parse($ed->joining_date)->format(company()->date_format)
            : '-';

        $dayColumns = [];
        $woCount = 0;
        $holidayCount = 0;
        $presentFull = 0;
        $halfDayCount = 0;

        $leaveByDate = Leave::with('type')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('leave_date', [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->get()
            ->groupBy(fn ($l) => Carbon::parse($l->leave_date)->toDateString());

        $attendanceByDate = Attendance::where('user_id', $user->id)
            ->whereBetween(DB::raw('DATE(clock_in_time)'), [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->get()
            ->groupBy(function ($a) {
                return $a->clock_in_time->timezone(company()->timezone)->toDateString();
            });

        foreach ($this->period as $date) {
            $dateStr = $date->toDateString();
            $dayOfWeek = (int) $date->format('w'); // 0=Sun, 1=Mon, ..., 6=Sat
            $isWeekOff = !in_array($dayOfWeek, $this->officeOpenDays);
            $isHoliday = isset($this->holidayDates[$dateStr]);
            $attendanceOnDate = $attendanceByDate->get($dateStr);
            $leaveOnDate = $leaveByDate->get($dateStr);
            $daySummary = self::summarizeDayForExport(
                hasAttendance: $attendanceOnDate && $attendanceOnDate->isNotEmpty(),
                hasHalfDayAttendance: $attendanceOnDate && $attendanceOnDate->contains('half_day', 'yes'),
                isWeekOff: $isWeekOff,
                isHoliday: $isHoliday,
                hasLeave: $leaveOnDate && $leaveOnDate->isNotEmpty(),
                leaveCode: $this->leaveCodeForExport($leaveOnDate)
            );

            $dayColumns[] = $daySummary['code'];
            $woCount += $daySummary['week_offs'];
            $holidayCount += $daySummary['holidays'];
            $presentFull += $daySummary['present_full'];
            $halfDayCount += $daySummary['half_days'];
        }

        $standardDays = 0;
        foreach ($this->period as $date) {
            $dayOfWeek = (int) $date->format('w');
            if (in_array($dayOfWeek, $this->officeOpenDays)) {
                $standardDays++;
            }
        }

        $workingDays = $presentFull + ($halfDayCount * 0.5);

        $leaveCounts = $this->getLeaveCountsByType($user->id);
        $sl = $leaveCounts['SL'];
        $el = $leaveCounts['EL'];
        $cl = $leaveCounts['CL'];
        $lwp = $leaveCounts['LWP'];
        $paidLeaveDays = $leaveCounts['paid_days'];

        $totalLeaves = $sl + $el + $cl + $lwp;
        $paidDays = round($workingDays + $paidLeaveDays, 1);

        $row = array_merge(
            [$srNo, $empId, $empName, $designation, $department, $hq, $doj],
            $dayColumns,
            [
                $standardDays,
                round($workingDays, 1),
                $woCount,
                $holidayCount,
                $sl,
                $el,
                $cl,
                $lwp,
                $halfDayCount,
                round($totalLeaves, 1),
                $paidDays,
            ]
        );

        return $row;
    }

    public static function summarizeDayForExport(
        bool $hasAttendance,
        bool $hasHalfDayAttendance,
        bool $isWeekOff,
        bool $isHoliday,
        bool $hasLeave,
        ?string $leaveCode = null
    ): array {
        if ($hasAttendance) {
            return [
                'code' => $hasHalfDayAttendance ? 'HF' : 'P',
                'working_days' => $hasHalfDayAttendance ? 0.5 : 1.0,
                'present_full' => $hasHalfDayAttendance ? 0 : 1,
                'half_days' => $hasHalfDayAttendance ? 1 : 0,
                'week_offs' => 0,
                'holidays' => 0,
            ];
        }

        if ($isHoliday) {
            return [
                'code' => 'H',
                'working_days' => 0.0,
                'present_full' => 0,
                'half_days' => 0,
                'week_offs' => 0,
                'holidays' => 1,
            ];
        }

        if ($isWeekOff) {
            return [
                'code' => 'WO',
                'working_days' => 0.0,
                'present_full' => 0,
                'half_days' => 0,
                'week_offs' => 1,
                'holidays' => 0,
            ];
        }

        return [
            'code' => $hasLeave ? ($leaveCode ?: 'L') : '',
            'working_days' => 0.0,
            'present_full' => 0,
            'half_days' => 0,
            'week_offs' => 0,
            'holidays' => 0,
        ];
    }

    protected function leaveCodeForExport($leaveOnDate): ?string
    {
        if (!$leaveOnDate || $leaveOnDate->isEmpty()) {
            return null;
        }

        $leave = $leaveOnDate->first();
        $isPaid = (isset($leave->paid) ? (bool) $leave->paid : ($leave->type && $leave->type->paid));

        return $isPaid ? $this->mapLeaveTypeToColumn($leave->type ? $leave->type->type_name : '') : 'LWP';
    }

    protected function getLeaveCountsByType(int $userId): array
    {
        $leaves = Leave::with('type')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('leave_date', [$this->startDate->toDateString(), $this->endDate->toDateString()])
            ->get();

        $sl = $cl = $el = $lwp = $paidDays = 0;

        foreach ($leaves as $leave) {
            $days = ($leave->duration === 'half day' || $leave->half_day_type) ? 0.5 : 1;
            $typeName = $leave->type ? $leave->type->type_name : '';
            $isPaid = (isset($leave->paid) ? (bool) $leave->paid : ($leave->type && $leave->type->paid));

            if (!$isPaid) {
                $lwp += $days;
            } else {
                $key = $this->mapLeaveTypeToColumn($typeName);
                if ($key === 'SL') {
                    $sl += $days;
                } elseif ($key === 'CL') {
                    $cl += $days;
                } elseif ($key === 'EL') {
                    $el += $days;
                }
            }

            if ($isPaid) {
                $paidDays += $days;
            }
        }

        return ['SL' => $sl, 'CL' => $cl, 'EL' => $el, 'LWP' => $lwp, 'paid_days' => $paidDays];
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
        if (str_contains($name, 'lwp') || str_contains($name, 'without pay') || str_contains($name, 'unpaid')) {
            return 'LWP';
        }
        return 'Other';
    }
}
