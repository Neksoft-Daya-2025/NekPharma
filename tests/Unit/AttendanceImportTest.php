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

        $this->assertContains('email', $fields);
        $this->assertContains('month', $fields);
        $this->assertContains('day_1', $fields);
        $this->assertContains('day_31', $fields);
        $this->assertNotContains('date', $fields);
    }

    public function test_monthly_row_expands_statuses_into_attendance_days(): void
    {
        $rows = ImportAttendanceJob::monthlyAttendanceRows(
            [
                'employee@example.com',
                '2026-05',
                'present',
                'absent',
                'half_day',
                'late',
            ],
            [
                0 => 'email',
                1 => 'month',
                2 => 'day_1',
                3 => 'day_2',
                4 => 'day_3',
                5 => 'day_4',
            ]
        );

        $this->assertSame([
            ['date' => '2026-05-01', 'status' => 'present'],
            ['date' => '2026-05-03', 'status' => 'half_day'],
            ['date' => '2026-05-04', 'status' => 'late'],
        ], $rows);
    }
}
