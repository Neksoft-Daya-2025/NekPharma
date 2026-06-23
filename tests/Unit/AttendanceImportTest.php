<?php

namespace Tests\Unit;

use App\Imports\AttendanceImport;
use App\Jobs\ImportAttendanceJob;
use App\Http\Controllers\AttendanceController;
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
                'HF',
            ],
            [
                0 => 'employee_id',
                1 => 'month',
                2 => 'day_1',
                3 => 'day_2',
                4 => 'day_3',
                5 => 'day_4',
                6 => 'day_5',
            ]
        );

        $this->assertSame([
            ['date' => '2026-05-01', 'status' => 'Present'],
            ['date' => '2026-05-03', 'status' => 'SL'],
            ['date' => '2026-05-04', 'status' => 'LWP'],
            ['date' => '2026-05-05', 'status' => 'Half Day'],
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
        $this->assertSame('Half Day', ImportAttendanceJob::normalizeAttendanceImportStatus('hf'));
        $this->assertSame('Week Off', ImportAttendanceJob::normalizeAttendanceImportStatus('wo'));
        $this->assertNull(ImportAttendanceJob::normalizeAttendanceImportStatus('late'));
    }

    public function test_import_job_can_include_absent_rows_to_clear_existing_days(): void
    {
        $rows = ImportAttendanceJob::monthlyAttendanceRows(
            ['RVB001', '2026-05', 'absent'],
            [0 => 'employee_id', 1 => 'month', 2 => 'day_1'],
            true
        );

        $this->assertSame([
            ['date' => '2026-05-01', 'status' => 'Absent'],
        ], $rows);
    }

    public function test_import_job_can_include_week_off_rows_to_clear_existing_days(): void
    {
        $rows = ImportAttendanceJob::monthlyAttendanceRows(
            ['RVB001', '2026-05', 'WO'],
            [0 => 'employee_id', 1 => 'month', 2 => 'day_1'],
            true
        );

        $this->assertSame([
            ['date' => '2026-05-01', 'status' => 'Week Off'],
        ], $rows);
    }

    public function test_import_job_identifies_leave_statuses(): void
    {
        $this->assertTrue(ImportAttendanceJob::isLeaveImportStatus('SL'));
        $this->assertTrue(ImportAttendanceJob::isLeaveImportStatus('CL'));
        $this->assertTrue(ImportAttendanceJob::isLeaveImportStatus('EL'));
        $this->assertTrue(ImportAttendanceJob::isLeaveImportStatus('LWP'));
        $this->assertFalse(ImportAttendanceJob::isLeaveImportStatus('Present'));
    }

    public function test_attendance_template_starts_with_employee_id(): void
    {
        $this->assertSame([
            'Employee ID',
            'name (reference only)',
            'designation (reference only)',
            'department (reference only)',
            'month',
        ], AttendanceController::attendanceTemplateBaseColumns());
    }

    public function test_new_template_header_order_maps_to_import_columns(): void
    {
        $headings = array_merge(
            AttendanceController::attendanceTemplateBaseColumns(),
            ['2026-05-01', '2026-05-02']
        );

        $columns = array_map(
            fn ($heading) => AttendanceImport::columnIdForHeading($heading),
            $headings
        );

        $this->assertSame('employee_id', $columns[0]);
        $this->assertSame('month', $columns[4]);
        $this->assertSame('day_1', $columns[5]);
        $this->assertSame('day_2', $columns[6]);
    }

    public function test_import_screen_instructions_show_half_day_and_sample_row(): void
    {
        $view = file_get_contents(base_path('resources/views/attendances/ajax/import.blade.php'));

        $this->assertStringContainsString('Allowed attendance values', $view);
        $this->assertStringContainsString('<code>HF</code>', $view);
        $this->assertStringContainsString('Sample fill', $view);
        $this->assertStringContainsString('RVB / 105', $view);
        $this->assertStringContainsString('Half Day', $view);
    }
}
