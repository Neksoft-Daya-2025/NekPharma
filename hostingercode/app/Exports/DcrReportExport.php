<?php

namespace App\Exports;

use App\Services\DcrReportReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DcrReportExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $accessibleHqIds = $this->filters['accessibleHqIds'] ?? null;
        return DcrReportReportService::getVisitRows($this->filters, $accessibleHqIds);
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('app.employee'),
            __('app.role'),
            __('app.hq'),
            __('app.stationType'),
            __('app.partyName'),
            __('app.partyType'),
            __('app.product'),
            __('app.visitTime'),
            __('app.remarks'),
        ];
    }

    public function map($row): array
    {
        $company = company();
        $dateFormatted = $row->date instanceof \Carbon\Carbon
            ? $row->date->format($company->date_format)
            : Carbon::parse($row->date)->format($company->date_format);

        return [
            $dateFormatted,
            $row->employee_name ?? '-',
            $row->role ?? '-',
            $row->headquarter ?? '-',
            $row->station_type ?? '-',
            $row->party_name ?? '-',
            $row->party_type ?? '-',
            $row->product ?? '-',
            $row->visit_time ?? '-',
            $row->remarks ?? '-',
        ];
    }
}
