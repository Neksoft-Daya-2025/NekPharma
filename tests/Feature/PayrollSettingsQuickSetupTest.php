<?php

namespace Tests\Feature;

use Tests\TestCase;

class PayrollSettingsQuickSetupTest extends TestCase
{
    public function test_quick_setup_partial_shows_primary_payroll_settings_actions(): void
    {
        $html = view('payroll::payroll-setting.ajax.quick-setup')->render();

        $this->assertStringContainsString('Quick Payroll Setup', $html);
        $this->assertStringContainsString('Payroll Currency', $html);
        $this->assertStringContainsString('Salary Components', $html);
        $this->assertStringContainsString('Salary Groups', $html);
        $this->assertStringContainsString('Tax / TDS', $html);
        $this->assertStringContainsString('Payment Methods', $html);
        $this->assertStringContainsString('Payslip Fields', $html);
    }
}
