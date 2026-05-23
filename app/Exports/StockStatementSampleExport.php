<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockStatementSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $products = Product::where('company_id', company()->id)
            ->orderBy('name')
            ->limit(3)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        if (empty($products)) {
            return [
                ['SIMCOBAL FORTE', '100', '50', '30', '120'],
                ['HOVER-CV168', '80', '40', '25', '95'],
            ];
        }

        $rows = [];
        foreach ($products as $index => $name) {
            $opening = 100 - ($index * 20);
            $primary = 50 - ($index * 10);
            $secondary = 30 - ($index * 5);
            $closing = $opening + $primary - $secondary;
            $rows[] = [(string) $name, (string) $opening, (string) $primary, (string) $secondary, (string) $closing];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Product',
            'Opening Qty',
            'Primary Qty',
            'Secondary Qty',
            'Closing Qty',
        ];
    }
}
