<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LedgerPresentationTest extends TestCase
{
    public function test_ledger_all_party_requests_return_grouped_rows_instead_of_empty_rows(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/LedgerController.php');

        $this->assertStringContainsString('buildAllCFALedgerGroups', $controller);
        $this->assertStringContainsString('buildAllCFAStockistLedgerGroups', $controller);
        $this->assertStringContainsString("'groups' => \$this->buildAllCFALedgerGroups", $controller);
        $this->assertStringContainsString("'groups' => \$this->buildAllCFAStockistLedgerGroups", $controller);
        $this->assertStringNotContainsString("return Reply::dataOnly(['rows' => [], 'party_name' => '', 'opening_balance' => 0]);", $controller);
    }

    public function test_ledger_views_render_grouped_all_party_responses(): void
    {
        $cfaLedger = file_get_contents(__DIR__ . '/../../resources/views/ledger/cfa-ledger.blade.php');
        $stockistLedger = file_get_contents(__DIR__ . '/../../resources/views/ledger/cfa-stockist-ledger.blade.php');

        foreach ([$cfaLedger, $stockistLedger] as $view) {
            $this->assertStringContainsString('renderLedgerGroups(res.groups)', $view);
            $this->assertStringContainsString('function renderLedgerGroups(groups)', $view);
            $this->assertStringContainsString('function renderOpeningRow(opening)', $view);
            $this->assertStringNotContainsString("partyId === 'all' || !partyId", $view);
        }
    }
}
