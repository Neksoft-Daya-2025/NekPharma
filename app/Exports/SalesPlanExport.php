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
            'Headquarter',
            'Assigned Employees',
            'Product',
            'Target Qty',
            'Target Amount',
            'Notes',
        ];
    }

    public function map($target): array
    {
        return [
            \Carbon\Carbon::create()->month((int) $target->period_month)->format('F') . ' ' . $target->period_year,
            $target->headquarter->name ?? '-',
            $target->assigned_employee_names ?? '-',
            $target->product->name ?? '-',
            (float) ($target->target_qty ?? 0),
            (float) $target->target_amount,
            $target->notes ?? '',
        ];
    }
}
