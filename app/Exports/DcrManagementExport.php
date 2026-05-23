<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DcrManagementExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $reports)
    {
    }

    public function collection(): Collection
    {
        return $this->reports->flatMap(function ($report) {
            $rows = collect();

            foreach ($report->doctorVisits as $visit) {
                $rows->push($this->visitRow($report, 'Doctor', $visit->doctor->fullname ?? $visit->doctor_name ?? '-', $visit->speciality ?? '-', $this->products($visit), $visit->pob, $visit->general_remark ?? null));
            }

            foreach ($report->chemistVisits as $visit) {
                $rows->push($this->visitRow($report, 'Chemist', $visit->chemist->shopname ?? $visit->chemist_name ?? '-', null, $this->rcpa($visit), null, $visit->general_remark ?? null));
            }

            foreach ($report->stockistVisits as $visit) {
                $rows->push($this->visitRow($report, 'Stockist', $visit->stockist->shopname ?? $visit->stockist_name ?? '-', null, null, $visit->pob ?? null, $visit->remark ?? null));
            }

            if ($rows->isEmpty()) {
                if ($report->doctor_id) {
                    $rows->push($this->visitRow($report, 'Doctor', optional($report->doctor)->fullname ?? '-', $report->speciality, collect([$report->product1, $report->product2, $report->product3])->filter()->implode(', '), $report->pob, $report->doctor_general_remark));
                }

                if ($report->chemist_id) {
                    $rows->push($this->visitRow($report, 'Chemist', optional($report->chemist)->shopname ?? '-', null, collect([$report->rcpa1, $report->rcpa2, $report->rcpa3, $report->rcpa4])->filter()->implode(', '), null, $report->chemist_general_remark));
                }

                if ($report->stockist_id) {
                    $rows->push($this->visitRow($report, 'Stockist', optional($report->stockist)->shopname ?? '-', null, null, $report->pob_stockist ?? $report->stockist_pob_amount, $report->stockist_general_remark ?? $report->stockist_remark));
                }
            }

            return $rows;
        });
    }

    public function headings(): array
    {
        return ['Date', 'Employee', 'HQ', 'Station', 'Work Status', 'Party Type', 'Party Name', 'Speciality', 'Products/RCPA', 'POB', 'Remark', 'Status'];
    }

    public function map($row): array
    {
        return $row;
    }

    private function visitRow($report, string $partyType, string $partyName, ?string $speciality, ?string $products, $pob, ?string $remark): array
    {
        return [
            optional($report->report_date)->format(company()->date_format) ?? $report->report_date,
            optional($report->user)->name ?? '-',
            $report->headquarter ?? '-',
            $report->station ?? '-',
            $report->work_status ?? '-',
            $partyType,
            $partyName,
            $speciality ?? '-',
            $products ?: '-',
            $pob ?? '-',
            $remark ?? $report->remark ?? '-',
            $report->status ?? ($report->approved ? 'approved' : 'pending'),
        ];
    }

    private function products($visit): string
    {
        return collect([$visit->product1, $visit->product2, $visit->product3])->filter()->implode(', ');
    }

    private function rcpa($visit): string
    {
        return collect([$visit->rcpa1, $visit->rcpa2, $visit->rcpa3, $visit->rcpa4])->filter()->implode(', ');
    }
}
