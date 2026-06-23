<?php

namespace Tests\Unit;

use App\Exports\AttendanceSheetFormatExport;
use PHPUnit\Framework\TestCase;

class AttendanceSheetFormatExportTest extends TestCase
{
    public function test_attendance_import_template_does_not_define_dropdown_validations(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/AttendanceController.php');

        $this->assertStringNotContainsString('DataValidation::TYPE_LIST', $controller);
        $this->assertStringNotContainsString('setShowDropDown', $controller);
    }

    public function test_attendance_sheet_summary_header_uses_week_off_label(): void
    {
        $export = file_get_contents(__DIR__ . '/../../app/Exports/AttendanceSheetFormatExport.php');

        $this->assertStringContainsString("'Week Off'", $export);
        $this->assertStringNotContainsString("'WO',\n            'Holiday'", $export);
    }

    public function test_attendance_on_holiday_exports_as_present(): void
    {
        $summary = AttendanceSheetFormatExport::summarizeDayForExport(
            hasAttendance: true,
            hasHalfDayAttendance: false,
            isWeekOff: false,
            isHoliday: true,
            hasLeave: false
        );

        $this->assertSame('P', $summary['code']);
        $this->assertSame(1.0, $summary['working_days']);
        $this->assertSame(0, $summary['holidays']);
    }

    public function test_attendance_on_week_off_exports_as_present(): void
    {
        $summary = AttendanceSheetFormatExport::summarizeDayForExport(
            hasAttendance: true,
            hasHalfDayAttendance: true,
            isWeekOff: true,
            isHoliday: false,
            hasLeave: false
        );

        $this->assertSame('HF', $summary['code']);
        $this->assertSame(0.5, $summary['working_days']);
        $this->assertSame(0, $summary['week_offs']);
    }

    public function test_leave_day_exports_the_specific_leave_code(): void
    {
        $summary = AttendanceSheetFormatExport::summarizeDayForExport(
            hasAttendance: false,
            hasHalfDayAttendance: false,
            isWeekOff: false,
            isHoliday: false,
            hasLeave: true,
            leaveCode: 'SL'
        );

        $this->assertSame('SL', $summary['code']);
    }
}
