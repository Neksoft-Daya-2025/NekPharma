<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EnterpriseGuardrailTest extends TestCase
{
    public function test_stock_statement_empty_geography_does_not_expand_to_all_stockists(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/StockStatementController.php');

        $this->assertStringContainsString("whereRaw('1 = 0')", $controller);
    }

    public function test_sales_plan_targets_are_scoped_to_accessible_geography(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/SalesPlanController.php');

        $this->assertStringContainsString('applyAccessibleTargetScope($query)', $controller);
        $this->assertStringContainsString('ensureSalesPlanScopeAccessible', $controller);
    }

    public function test_dcr_inline_master_creation_checks_headquarter_access(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/DcrReportController.php');

        $this->assertSame(3, substr_count($controller, 'ensureInlineHeadquarterAccessible((int) $request->headquarter_id);'));
    }

    public function test_cfa_stockist_invoice_stock_source_is_owned_by_selected_distributor(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/InvoiceController.php');

        $this->assertStringContainsString('->where(\'cfa_distributor_id\', $request->cfa_distributor_id)', $controller);
        $this->assertStringContainsString('->where(\'product_id\', $request->product_id[$key])', $controller);
        $this->assertStringContainsString('DB::beginTransaction();', $controller);
    }

    public function test_payment_edits_must_belong_to_the_submitted_invoice(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/InvoiceController.php');

        $this->assertStringContainsString('Payment::where(\'invoice_id\', $invoice->id)->findOrFail($request->edit_payment_id)', $controller);
        $this->assertStringContainsString('recalculateInvoicePaymentStatus($invoice)', $controller);
    }

    public function test_enterprise_audit_log_schema_and_helper_exist(): void
    {
        $migration = file_get_contents(__DIR__ . '/../../database/migrations/2026_05_23_000001_create_enterprise_audit_logs_table.php');
        $helper = file_get_contents(__DIR__ . '/../../app/Support/EnterpriseAudit.php');

        $this->assertStringContainsString("Schema::create('enterprise_audit_logs'", $migration);
        $this->assertStringContainsString("'before'", $migration);
        $this->assertStringContainsString("'after'", $migration);
        $this->assertStringContainsString("'actor_id'", $migration);
        $this->assertStringContainsString('EnterpriseAuditLog::create', $helper);
    }

    public function test_critical_mutations_write_enterprise_audit_events(): void
    {
        $invoiceController = file_get_contents(__DIR__ . '/../../app/Http/Controllers/InvoiceController.php');
        $dcrController = file_get_contents(__DIR__ . '/../../app/Http/Controllers/DcrReportController.php');
        $salesPlanController = file_get_contents(__DIR__ . '/../../app/Http/Controllers/SalesPlanController.php');

        foreach ([
            'payment.created',
            'payment.updated',
            'payment.deleted',
            'cfa_stockist_stock.created_from_distributor_stock',
        ] as $event) {
            $this->assertStringContainsString($event, $invoiceController);
        }

        foreach (['dcr_report.approved', 'dcr_report.rejected', 'dcr_report.bulk_approved'] as $event) {
            $this->assertStringContainsString($event, $dcrController);
        }

        foreach (['sales_plan_target.created', 'sales_plan_target.updated', 'sales_plan_target.deleted'] as $event) {
            $this->assertStringContainsString($event, $salesPlanController);
        }
    }
}
