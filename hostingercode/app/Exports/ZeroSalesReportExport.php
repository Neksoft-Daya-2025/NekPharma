<?php

namespace App\Exports;

use App\Services\ZeroSalesReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ZeroSalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return ZeroSalesReportService::getReportRows($this->filters);
    }

    public function getCollection()
    {
        return $this->collection();
    }

    public function headings(): array
    {
        return [
            'Entity Type',
            'Entity Name',
            __('app.hq'),
            'Area',
            'Region',
        ];
    }

    public function map($row): array
    {
        return [
            $row->entity_type ?? '-',
            $row->entity_name ?? '-',
            $row->hq_name ?? '-',
            $row->area_name ?? '-',
            $row->region_name ?? '-',
        ];
    }
}
