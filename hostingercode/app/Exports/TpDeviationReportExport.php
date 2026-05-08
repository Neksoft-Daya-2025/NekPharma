<?php

namespace App\Exports;

use App\Services\TpDeviationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TpDeviationReportExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return TpDeviationReportService::buildRows($this->filters);
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('app.name'),
            __('app.employeeId'),
            __('app.type'),
            'Tour work status',
            'DCR work status',
            'Tour station',
            'DCR station',
            'Tour ' . __('app.hq'),
            'DCR ' . __('app.hq'),
        ];
    }

    public function map($row): array
    {
        $typeKey = 'app.tpDeviationTypes.' . ($row->deviation_type ?? '');

        return [
            $row->report_date ?? '-',
            $row->employee_name ?? '-',
            $row->employee_code ?? '-',
            __($typeKey),
            $row->tour_work_status ?? '-',
            $row->dcr_work_status ?? '-',
            $row->tour_station ?? '-',
            $row->dcr_station ?? '-',
            $row->tour_headquarter ?? '-',
            $row->dcr_headquarter ?? '-',
        ];
    }
}
