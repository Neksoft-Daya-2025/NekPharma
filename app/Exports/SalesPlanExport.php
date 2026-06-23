<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesPlanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $targets)
    {
    }

    public function collection(): Collection
    {
        return $this->targets;
    }

    public function headings(): array
    {
        return [
            'Period',
            'Plan Level',
            'Scope',
            'Product',
            'Target Amount',
            'Notes',
        ];
    }

    public function map($target): array
    {
        return [
            \Carbon\Carbon::create()->month((int) $target->period_month)->format('F') . ' ' . $target->period_year,
            ucfirst((string) $target->plan_level),
            $target->scope_name,
            $target->product->name ?? 'All Products',
            (float) $target->target_amount,
            $target->notes ?? '',
        ];
    }
}
