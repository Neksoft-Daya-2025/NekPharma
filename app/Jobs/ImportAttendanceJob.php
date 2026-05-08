<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\User;
use App\Traits\ExcelImportable;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
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
     * New CSV format: email, date (YYYY-MM-DD), status (present|absent|half_day|late)
     * Clock-in / clock-out times are taken from AttendanceSetting (office_start_time / office_end_time).
     *
     * @return void
     */
    public function handle()
    {
        // Validate required columns
        if (!$this->isColumnExists('email') || !$this->isColumnExists('date') || !$this->isColumnExists('status')) {
            $this->failJob(__('messages.invalidData'));
            return;
        }

        if (!$this->isEmailValid($this->getColumnValue('email'))) {
            $this->failJob(__('messages.invalidData'));
            return;
        }

        $status = strtolower(trim($this->getColumnValue('status')));

        // "absent" means no record — skip silently
        if ($status === 'absent') {
            return;
        }

        if (!in_array($status, ['present', 'half_day', 'late'])) {
            $this->failJobWithMessage('Invalid status "' . $status . '". Allowed: present, absent, half_day, late.');
            return;
        }

        // Find employee
        $user = User::where('email', $this->getColumnValue('email'))
            ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->first();

        if (!$user) {
            $this->failJobWithMessage(__('messages.employeeNotFound'));
            return;
        }

        // Validate and parse the date column
        try {
            $date = Carbon::createFromFormat('Y-m-d', trim($this->getColumnValue('date')));
        } catch (\Exception $e) {
            $this->failJobWithMessage('Invalid date format. Expected YYYY-MM-DD, got: ' . $this->getColumnValue('date'));
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

        // Build clock-in time based on status
        $clockInDateTime  = Carbon::parse($date->format('Y-m-d') . ' ' . $officeStart, $timezone);
        $clockOutDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $officeEnd, $timezone);

        $late    = 'no';
        $halfDay = 'no';

        if ($status === 'late') {
            $clockInDateTime->addMinutes($lateMinutes);
            $late = 'yes';
        } elseif ($status === 'half_day') {
            $clockOutDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $halfdayTime, $timezone);
            $halfDay = 'yes';
        }

        DB::beginTransaction();
        try {
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

            DB::commit();
        } catch (InvalidFormatException $e) {
            DB::rollBack();
            $this->failJob(__('messages.invalidDate'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->failJobWithMessage($e->getMessage());
        }
    }

}
