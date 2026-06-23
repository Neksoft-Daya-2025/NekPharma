<?php

namespace App\Exports;

use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesPlanSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $headquarter = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->value('name') ?: 'Ahmedabad HQ';
        $area = PharmaArea::where('company_id', company()->id)->orderBy('name')->value('name') ?: 'West Area';
        $region = PharmaRegion::where('company_id', company()->id)->orderBy('name')->value('name') ?: 'North Region';
        $product = Product::where('company_id', company()->id)->orderBy('name')->value('name') ?: '';

        return [
            ['5', date('Y'), 'headquarter', $headquarter, $product, '100000', 'HQ monthly sales plan'],
            ['5', date('Y'), 'area', $area, '', '250000', 'Area monthly sales plan'],
            ['5', date('Y'), 'region', $region, '', '500000', 'Region monthly sales plan'],
        ];
    }

    public function headings(): array
    {
        return [
            'Period Month',
            'Period Year',
            'Plan Level',
            'Scope',
            'Product',
            'Target Amount',
            'Notes',
        ];
    }
}
