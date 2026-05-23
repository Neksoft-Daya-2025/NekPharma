<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CFAStockistInvoicePresentationTest extends TestCase
{
    public function test_cfa_stockist_invoice_list_has_a_tax_type_column_and_badge_renderer(): void
    {
        $dataTable = file_get_contents(__DIR__ . '/../../app/DataTables/CFAStockistInvoicesDataTable.php');

        $this->assertStringContainsString("addColumn('tax_type'", $dataTable);
        $this->assertStringContainsString("'invoices.invoice_type'", $dataTable);
        $this->assertStringContainsString('CGST', $dataTable);
        $this->assertStringContainsString('IGST', $dataTable);
    }

    public function test_cfa_stockist_igst_invoice_reuses_the_cgst_invoice_format_shell(): void
    {
        $igstInvoice = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-stockist/igst-invoice.blade.php');
        $cgstInvoice = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-stockist/pharma-invoice.blade.php');

        $this->assertStringContainsString('pharma-tax-invoice-header', $cgstInvoice);
        $this->assertStringContainsString("@include('invoices.cfa-stockist.pharma-invoice'", $igstInvoice);
        $this->assertStringNotContainsString('header-main-table', $igstInvoice);
    }

    public function test_cfa_stockist_igst_show_uses_the_same_action_wrapper_as_cgst_show(): void
    {
        $igstShow = file_get_contents(__DIR__ . '/../../resources/views/invoices/cfa-stockist/igst-show.blade.php');

        $this->assertStringContainsString('d-flex justify-content-end mb-3 no-print', $igstShow);
        $this->assertSame(1, substr_count($igstShow, 'card border-0 invoice'));
        $this->assertStringContainsString("@include('invoices.cfa-stockist.igst-invoice')", $igstShow);
    }
}
