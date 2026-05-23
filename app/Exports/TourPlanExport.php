<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TourPlanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $tours)
    {
    }

    public function collection(): Collection
    {
        return $this->tours;
    }

    public function headings(): array
    {
        return ['Date', 'Day', 'Employee', 'HQ', 'Submit To', 'Work Type', 'Station', 'Work With', 'Remark', 'Status', 'Approved By'];
    }

    public function map($tour): array
    {
        return [
            optional($tour->date)->format(company()->date_format) ?? $tour->date,
            $tour->day ?? optional($tour->date)->format('l'),
            optional($tour->user)->name ?? '-',
            optional($tour->headquarter)->name ?? '-',
            optional($tour->submittedTo)->name ?? '-',
            $tour->work_status ?? '-',
            $tour->station ?? '-',
            $tour->work_with ?? '-',
            $tour->remark ?? '-',
            $tour->status ?? ($tour->approved ? 'approved' : 'pending'),
            optional($tour->approvedBy)->name ?? '-',
        ];
    }
}
