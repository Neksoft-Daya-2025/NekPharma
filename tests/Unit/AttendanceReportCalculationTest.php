<?php

namespace Tests\Unit;

use App\DataTables\AttendanceReportDataTable;
use PHPUnit\Framework\TestCase;

class AttendanceReportCalculationTest extends TestCase
{
    public function test_absent_days_do_not_double_count_holiday_attendance(): void
    {
        $this->assertSame(11, AttendanceReportDataTable::calculateAbsentDaysForReport(31, 19, 1, 2));
    }
}
