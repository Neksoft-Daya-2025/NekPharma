<?php

namespace Tests\Unit;

use App\Imports\DoctorImport;
use App\Jobs\ImportDoctorJob;
use Tests\TestCase;

class DoctorImportTest extends TestCase
{
    public function test_sample_headers_preserve_blank_column_indexes(): void
    {
        $map = DoctorImport::buildColumnIndexMap([
            'Dr. Name',
            'HQ',
            null,
            'Station Name',
            'Dr. Type (SFC)',
            'Qualification',
            'Station Type',
            'Address',
            'Speciality',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Products (comma-separated)',
            'Brand 2',
            'Brand 3',
            'MSL Number',
        ]);

        $this->assertSame('fullname', $map[0]);
        $this->assertSame('headquarter', $map[1]);
        $this->assertArrayNotHasKey(2, $map);
        $this->assertSame('station', $map[3]);
        $this->assertSame('doctor_type', $map[4]);
        $this->assertSame('qualification', $map[5]);
        $this->assertSame('station_type', $map[6]);
        $this->assertSame('msl_number', $map[17]);
    }

    public function test_duplicate_mobile_matching_uses_last_ten_digits(): void
    {
        $this->assertSame('9876543210', ImportDoctorJob::normalizeMobileForDuplicate('+91 98765 43210'));
        $this->assertSame('9876543210', ImportDoctorJob::normalizeMobileForDuplicate('09876543210'));
        $this->assertSame('9876543210', ImportDoctorJob::normalizeMobileForDuplicate('9.87654321E+9'));
    }

    public function test_duplicate_email_matching_is_case_insensitive(): void
    {
        $this->assertSame('doctor@example.com', ImportDoctorJob::normalizeEmailForDuplicate(' Doctor@Example.COM '));
        $this->assertNull(ImportDoctorJob::normalizeEmailForDuplicate('  '));
    }

    public function test_blank_excel_rows_are_removed_before_import(): void
    {
        $rows = DoctorImport::filterBlankRows([
            ['Doctor A', 'Balrampur'],
            [null, null, ''],
            ['', '   '],
            ['Doctor B', 'Balrampur'],
        ]);

        $this->assertSame([
            ['Doctor A', 'Balrampur'],
            ['Doctor B', 'Balrampur'],
        ], $rows);
    }

    public function test_geo_names_and_station_types_are_normalized(): void
    {
        $this->assertSame('balrampur', ImportDoctorJob::normalizeGeoName('Balram Pur'));
        $this->assertSame('headquarter', ImportDoctorJob::normalizeStationType('HQ'));
        $this->assertSame('exstation', ImportDoctorJob::normalizeStationType('Ex-Station'));
        $this->assertSame('outstation', ImportDoctorJob::normalizeStationType('Out Station'));
    }
}
