<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\User;
use App\Traits\ExcelImportable;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use InvalidArgumentException;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportAttendanceJob implements ShouldQueue
{

    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * Excel format: employee_id, month (YYYY-MM), date columns with statuses (SL|CL|EL|LWP|Present|Absent)
     * Clock-in / clock-out times are taken from AttendanceSetting (office_start_time / office_end_time).
     *
     * @return void
     */
    public function handle()
    {
        // Validate required columns
        if (!$this->isColumnExists('employee_id') || !$this->isColumnExists('month')) {
            $this->failJob(__('messages.invalidData'));
            return;
        }

        $employeeId = trim((string) $this->getColumnValue('employee_id'));

        if ($employeeId === '') {
            $this->failJob(__('messages.invalidData'));
            return;
        }

        // Find employee
        $user = User::whereHas('employeeDetail', fn($q) => $q->where('employee_id', $employeeId))
            ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->first();

        if (!$user) {
            $this->failJobWithMessage(__('messages.employeeNotFound'));
            return;
        }

        try {
            $attendanceRows = self::monthlyAttendanceRows($this->row, $this->columns);
        } catch (InvalidArgumentException $e) {
            $this->failJobWithMessage($e->getMessage());
            return;
        }

        if (empty($attendanceRows)) {
            return;
        }

        // Load office hours from AttendanceSetting (company-scoped)
        $setting = \App\Models\AttendanceSetting::where('company_id', $this->company?->id)->first();

        // Defaults if settings are missing
        $officeStart = $setting?->office_start_time ?? '09:00:00';
        $officeEnd   = $setting?->office_end_time   ?? '18:00:00';
        $halfdayTime = $setting?->halfday_mark_time  ?? '13:00:00';
        $lateMinutes = (int) ($setting?->late_mark_duration ?? 30);
        $timezone    = $this->company?->timezone ?? 'UTC';

        DB::beginTransaction();
        try {
            foreach ($attendanceRows as $attendanceRow) {
                $date = $attendanceRow['date'];
                $status = $attendanceRow['status'];

                if ($status !== 'Present') {
                    continue;
                }

                $clockInDateTime  = Carbon::parse($date . ' ' . $officeStart, $timezone);
                $clockOutDateTime = Carbon::parse($date . ' ' . $officeEnd, $timezone);

                $late    = 'no';
                $halfDay = 'no';

                Attendance::create([
                    'company_id'     => $this->company?->id,
                    'user_id'        => $user->id,
                    'clock_in_time'  => $clockInDateTime->utc()->format('Y-m-d H:i:s'),
                    'clock_in_ip'    => '127.0.0.1',
                    'clock_out_time' => $clockOutDateTime->utc()->format('Y-m-d H:i:s'),
                    'clock_out_ip'   => '127.0.0.1',
                    'working_from'   => 'office',
                    'late'           => $late,
                    'half_day'       => $halfDay,
                ]);
            }

            DB::commit();
        } catch (InvalidFormatException $e) {
            DB::rollBack();
            $this->failJob(__('messages.invalidDate'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->failJobWithMessage($e->getMessage());
        }
    }

    public static function monthlyAttendanceRows(array $row, array $columns): array
    {
        $monthIndex = array_search('month', $columns, true);

        if ($monthIndex === false || empty($row[$monthIndex])) {
            throw new InvalidArgumentException('Invalid month format. Expected YYYY-MM.');
        }

        try {
            $month = Carbon::createFromFormat('!Y-m', trim((string) $row[$monthIndex]));
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid month format. Expected YYYY-MM, got: ' . $row[$monthIndex]);
        }

        $attendanceRows = [];
        for ($day = 1; $day <= 31; $day++) {
            $dayIndex = array_search('day_' . $day, $columns, true);

            if ($dayIndex === false || !isset($row[$dayIndex]) || trim((string) $row[$dayIndex]) === '') {
                continue;
            }

            $status = self::normalizeAttendanceImportStatus($row[$dayIndex]);

            if ($status === null) {
                throw new InvalidArgumentException('Invalid status "' . trim((string) $row[$dayIndex]) . '". Allowed: SL, CL, EL, LWP, Present, Absent.');
            }

            if ($day > $month->daysInMonth) {
                throw new InvalidArgumentException('Invalid day ' . $day . ' for month ' . $month->format('Y-m') . '.');
            }

            if ($status === 'Absent') {
                continue;
            }

            $attendanceRows[] = [
                'date' => $month->copy()->day($day)->format('Y-m-d'),
                'status' => $status,
            ];
        }

        return $attendanceRows;
    }

    public static function normalizeAttendanceImportStatus($status): ?string
    {
        $value = strtoupper(trim((string) $status));

        return match ($value) {
            'PRESENT' => 'Present',
            'ABSENT' => 'Absent',
            'SL' => 'SL',
            'CL' => 'CL',
            'EL' => 'EL',
            'LWP' => 'LWP',
            default => null,
        };
    }

}
