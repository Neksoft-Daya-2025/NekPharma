<?php

namespace Tests\Unit;

use Tests\TestCase;

class PayrollSalarySetupShortcutTest extends TestCase
{
    public function testPayrollEditModalShowsSalarySetupShortcut(): void
    {
        $view = file_get_contents(base_path('Modules/Payroll/Resources/views/payroll/ajax/edit-modal.blade.php'));

        $this->assertStringContainsString('salarySetupUrl', $view);
        $this->assertStringContainsString('openRightModal', $view);
        $this->assertStringContainsString('Salary setup', $view);
    }

    public function testPayrollEditControllerLoadsEmployeeSalarySetupContext(): void
    {
        $controller = file_get_contents(base_path('Modules/Payroll/Http/Controllers/PayrollController.php'));

        $this->assertStringContainsString('$this->employeeMonthlySalary', $controller);
        $this->assertStringContainsString('employee-salary.edit-salary', $controller);
        $this->assertStringContainsString('employee-salary.make-salary', $controller);
    }
}
