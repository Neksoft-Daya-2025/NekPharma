<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StockStatementCalculationTest extends TestCase
{
    public function test_stock_statement_closing_stock_subtracts_secondary_sales(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/StockStatementController.php');

        $this->assertStringContainsString('$closing = $closingInput !== null ? $closingInput : ($opening + $primary - $secondary);', $controller);
        $this->assertStringNotContainsString('$opening + $primary + $secondary', $controller);
    }

    public function test_target_vs_achievement_uses_secondary_quantity_for_secondary_achievement(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../app/Http/Controllers/StockStatementController.php');

        $this->assertStringContainsString("sum('stock_statement_lines.secondary_qty')", $controller);
        $this->assertStringNotContainsString("secondaryTotal = (float) (clone \$secondaryQ)->sum('stock_statement_lines.primary_qty')", $controller);
    }
}
