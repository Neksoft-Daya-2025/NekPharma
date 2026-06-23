<?php

namespace Tests\Unit;

use Tests\TestCase;

class PayrollSalaryGroupDesignationAssignmentTest extends TestCase
{
    public function testSalaryGroupDesignationMappingFilesArePresent(): void
    {
        $this->assertFileExists(base_path('Modules/Payroll/Entities/SalaryGroupDesignation.php'));
        $this->assertFileExists(base_path('Modules/Payroll/Database/Migrations/2026_05_27_120000_create_salary_group_designations_table.php'));

        $migration = file_get_contents(base_path('Modules/Payroll/Database/Migrations/2026_05_27_120000_create_salary_group_designations_table.php'));

        $this->assertStringContainsString('salary_group_designations', $migration);
        $this->assertStringContainsString('designation_id', $migration);
    }

    public function testSalaryGroupControllerSupportsDesignationAssignment(): void
    {
        $controller = file_get_contents(base_path('Modules/Payroll/Http/Controllers/SalaryGroupController.php'));
        $routes = file_get_contents(base_path('Modules/Payroll/Routes/web.php'));

        $this->assertStringContainsString('syncDesignations', $controller);
        $this->assertStringContainsString('assignMatchingDesignations', $controller);
        $this->assertStringContainsString('salary-groups/assign-matching-designations', $routes);
    }

    public function testSalaryGroupSettingsExposeDesignationControls(): void
    {
        $create = file_get_contents(base_path('Modules/Payroll/Resources/views/payroll-setting/create-salary-group-modal.blade.php'));
        $edit = file_get_contents(base_path('Modules/Payroll/Resources/views/payroll-setting/edit-salary-group-modal.blade.php'));
        $list = file_get_contents(base_path('Modules/Payroll/Resources/views/payroll-setting/ajax/salary-groups.blade.php'));
        $index = file_get_contents(base_path('Modules/Payroll/Resources/views/payroll-setting/index.blade.php'));

        $this->assertStringContainsString('designation_ids', $create);
        $this->assertStringContainsString('designation_ids', $edit);
        $this->assertStringContainsString('assign-matching-designations', $list);
        $this->assertStringContainsString('How auto assignment works', $list);
        $this->assertStringContainsString('Apply Matching Employees', $list);
        $this->assertStringContainsString('assign-matching-designations', $index);
    }
}
