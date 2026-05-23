<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImportProcessFormTest extends TestCase
{
    public function test_process_form_renders_array_preview_values(): void
    {
        $html = view('import.process-form', [
            'importClassName' => 'DoctorImport',
            'headingTitle' => 'Import Doctors',
            'file' => 'doctors.xlsx',
            'hasHeading' => true,
            'fileHeading' => [
                0 => ['Dr.', 'Name'],
                1 => 'HQ',
            ],
            'heading' => [
                0 => 'fullname',
                1 => 'headquarter',
            ],
            'columns' => [
                ['id' => 'fullname', 'name' => 'Dr. Name', 'required' => 'Yes'],
                ['id' => 'headquarter', 'name' => 'HQ', 'required' => 'Yes'],
                ['id' => 'email', 'name' => ['Email', 'Address'], 'required' => 'No'],
            ],
            'matchedColumns' => ['fullname', 'headquarter'],
            'importSample' => [
                [
                    0 => ['MUKESH', 'SRIVASTAVA'],
                    1 => 'Balrampur',
                ],
            ],
            'processRoute' => route('doctors.import.process'),
            'backRoute' => route('doctors.index'),
            'backButtonText' => 'Back Doctors',
            'autoSubmitImport' => false,
            'importProgressModule' => 'DoctorImport',
        ])->render();

        $this->assertStringContainsString('Dr., Name', $html);
        $this->assertStringContainsString('MUKESH, SRIVASTAVA', $html);
    }
}
