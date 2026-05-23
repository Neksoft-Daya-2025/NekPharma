<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChemistExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $chemists)
    {
    }

    public function collection(): Collection
    {
        return $this->chemists;
    }

    public function headings(): array
    {
        return [
            'Shop Name',
            'Name',
            'HQ',
            'Area',
            'Ex-Station',
            'Outstation',
            'Station Type',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Address',
            'MSL Number',
        ];
    }

    public function map($chemist): array
    {
        return [
            $chemist->shopname,
            $chemist->fullname,
            optional($chemist->headquarter)->name,
            optional($chemist->area)->name,
            optional($chemist->exstation)->name,
            optional($chemist->outstation)->name,
            $this->stationType($chemist),
            $chemist->mobile,
            $chemist->email,
            $chemist->gender,
            $this->formatDate($chemist->dob),
            $this->formatDate($chemist->dom),
            $chemist->address,
            $chemist->msl_number,
        ];
    }

    private function stationType($chemist): string
    {
        if ($chemist->exstation_id) {
            return 'Ex-Station';
        }

        if ($chemist->outstation_id) {
            return 'Outstation';
        }

        return 'HQ';
    }

    private function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        return is_object($date) && method_exists($date, 'format') ? $date->format('Y-m-d') : (string) $date;
    }
}
