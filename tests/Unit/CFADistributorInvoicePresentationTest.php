<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CFADistributorInvoicePresentationTest extends TestCase
{
    public function test_cfa_distributor_invoice_list_has_a_tax_type_column_and_badge_renderer(): void
    {
        $dataTable = file_get_contents(__DIR__ . '/../../app/DataTables/CFADistributorInvoicesDataTable.php');

        $this->assertStringContainsString("addColumn('tax_type'", $dataTable);
        $this->assertStringContainsString("'invoices.invoice_type'", $dataTable);
        $this->assertStringContainsString('CGST', $dataTable);
        $this->assertStringContainsString('IGST', $dataTable);
    }

    public function test_cfa_distributor_igst_show_uses_the_same_action_wrapper_as_cgst_show(): void
    {
        $cgstShow = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-distributor/pharma-show.blade.php');
        $igstShow = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-distributor/igst-show.blade.php');

        $this->assertStringContainsString('<div class="d-flex justify-content-end mb-3 no-print">', $igstShow);
        $this->assertStringContainsString("@include('invoices.cfa-distributor.igst-invoice')", $igstShow);
        $this->assertSame(
            substr_count($cgstShow, '<div class="card border-0 invoice">'),
            substr_count($igstShow, '<div class="card border-0 invoice">')
        );
    }

    public function test_cfa_distributor_igst_invoice_reuses_the_cgst_invoice_format_shell(): void
    {
        $igstInvoice = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-distributor/igst-invoice.blade.php');
        $cgstInvoice = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-distributor/pharma-invoice.blade.php');

        $this->assertStringContainsString('pharma-tax-invoice-header', $cgstInvoice);
        $this->assertStringContainsString("@include('invoices.cfa-distributor.pharma-invoice'", $igstInvoice);
        $this->assertStringNotContainsString('header-main-table', $igstInvoice);
    }
}
