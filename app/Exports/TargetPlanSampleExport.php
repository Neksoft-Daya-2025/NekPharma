<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TargetPlanSampleExport implements FromArray, WithHeadings
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
                ['ATLOCK-SP TAB.', '100', '10000'],
                ['AVEDINE M CREAM', '80', '8000'],
            ];
        }

        return collect($products)->map(function ($name, $index) {
            $qty = 100 - ($index * 20);
            $amount = $qty * 100;

            return [(string) $name, (string) $qty, (string) $amount];
        })->all();
    }

    public function headings(): array
    {
        return [
            'Product',
            'Target Qty',
            'Target Amount',
        ];
    }
}
