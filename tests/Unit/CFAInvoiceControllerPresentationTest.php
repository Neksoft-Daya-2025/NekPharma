<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CFAInvoiceControllerPresentationTest extends TestCase
{
    public function test_cfa_invoice_show_methods_do_not_replace_layout_user_with_invoice_client(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/InvoiceController.php');

        $this->assertStringContainsString('$this->invoiceClient = $this->invoice->client;', $controller);
        $this->assertStringContainsString('$this->user = user();', $controller);
        $this->assertStringNotContainsString('$this->user = $this->invoice->client;', $controller);
    }
}
