<?php

namespace Tests\Unit;

use App\Imports\AttendanceImport;
use App\Jobs\ImportAttendanceJob;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
{
    public function test_fields_describe_monthly_day_columns(): void
    {
        $fields = collect(AttendanceImport::fields())->pluck('id')->all();

        $this->assertContains('employee_id', $fields);
        $this->assertContains('month', $fields);
        $this->assertContains('day_1', $fields);
        $this->assertContains('day_31', $fields);
        $this->assertNotContains('email', $fields);
        $this->assertNotContains('date', $fields);
    }

    public function test_monthly_row_expands_statuses_into_attendance_days(): void
    {
        $rows = ImportAttendanceJob::monthlyAttendanceRows(
            [
                'RVB001',
                '2026-05',
                'present',
                'absent',
                'SL',
                'LWP',
            ],
            [
                0 => 'employee_id',
                1 => 'month',
                2 => 'day_1',
                3 => 'day_2',
                4 => 'day_3',
                5 => 'day_4',
            ]
        );

        $this->assertSame([
            ['date' => '2026-05-01', 'status' => 'Present'],
            ['date' => '2026-05-03', 'status' => 'SL'],
            ['date' => '2026-05-04', 'status' => 'LWP'],
        ], $rows);
    }

    public function test_full_date_headers_map_to_month_day_columns(): void
    {
        $this->assertSame('day_1', AttendanceImport::columnIdForHeading('2026-05-01'));
        $this->assertSame('day_25', AttendanceImport::columnIdForHeading('2026-05-25'));
        $this->assertSame('day_5', AttendanceImport::columnIdForHeading('05'));
        $this->assertSame('employee_id', AttendanceImport::columnIdForHeading('Employee ID'));
        $this->assertSame('employee_id', AttendanceImport::columnIdForHeading('employee_id'));
        $this->assertSame('month', AttendanceImport::columnIdForHeading('month'));
        $this->assertNull(AttendanceImport::columnIdForHeading('2026-05-32'));
    }

    public function test_attendance_status_values_are_limited_to_dropdown_options(): void
    {
        $this->assertSame('Present', ImportAttendanceJob::normalizeAttendanceImportStatus('present'));
        $this->assertSame('Absent', ImportAttendanceJob::normalizeAttendanceImportStatus(' Absent '));
        $this->assertSame('SL', ImportAttendanceJob::normalizeAttendanceImportStatus('sl'));
        $this->assertSame('CL', ImportAttendanceJob::normalizeAttendanceImportStatus('CL'));
        $this->assertSame('EL', ImportAttendanceJob::normalizeAttendanceImportStatus('el'));
        $this->assertSame('LWP', ImportAttendanceJob::normalizeAttendanceImportStatus('lwp'));
        $this->assertNull(ImportAttendanceJob::normalizeAttendanceImportStatus('late'));
    }
}
