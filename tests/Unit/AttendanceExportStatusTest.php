<?php

namespace Tests\Unit;

use App\Exports\AttendanceExport;
use PHPUnit\Framework\TestCase;

class AttendanceExportStatusTest extends TestCase
{
    public function test_present_attendance_exports_status_instead_of_time(): void
    {
        $this->assertSame('Present', AttendanceExport::statusForExportCell('--', 480));
    }

    public function test_late_attendance_exports_as_present(): void
    {
        $this->assertSame('Present', AttendanceExport::statusForExportCell('Present(Late)', 480));
    }

    public function test_absent_attendance_exports_absent_status(): void
    {
        $this->assertSame('Absent', AttendanceExport::statusForExportCell('Absent', 0));
    }
}
