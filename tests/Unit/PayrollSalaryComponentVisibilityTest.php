<?php

namespace Tests\Unit;

use Modules\Payroll\Http\Controllers\PayrollController;
use PHPUnit\Framework\TestCase;

class PayrollSalaryComponentVisibilityTest extends TestCase
{
    public function test_zero_salary_components_are_hidden_from_payslip(): void
    {
        $components = PayrollController::visibleSalaryComponents([
            'HRA' => 10500,
            'ESIC' => 0,
            'Employer ESIC' => '0.00',
            'PF' => 1800,
            'Blank' => '',
            'Total Hours' => 0,
        ]);

        $this->assertSame([
            'HRA' => 10500,
            'PF' => 1800,
            'Total Hours' => 0,
        ], $components);
    }

    public function test_unchecked_salary_components_are_hidden_from_payslip(): void
    {
        $components = PayrollController::visibleSalaryComponents([
            'HRA' => 10500,
            'ESIC' => 250,
            'PF' => 1800,
        ], ['ESIC']);

        $this->assertSame([
            'HRA' => 10500,
            'PF' => 1800,
        ], $components);
    }
}
