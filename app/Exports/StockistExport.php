<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockistExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $stockists)
    {
    }

    public function collection(): Collection
    {
        return $this->stockists;
    }

    public function headings(): array
    {
        return [
            'Shop Name',
            'Owner Name',
            'Owner Mobile',
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
            'DL Number',
            'GST Number',
            'MSL Number',
        ];
    }

    public function map($stockist): array
    {
        return [
            $stockist->shopname,
            $stockist->owner_name,
            $stockist->owner_mobile,
            $stockist->fullname,
            optional($stockist->headquarter)->name,
            optional($stockist->area)->name,
            optional($stockist->exstation)->name,
            optional($stockist->outstation)->name,
            $this->stationType($stockist),
            $stockist->mobile,
            $stockist->email,
            $stockist->gender,
            $this->formatDate($stockist->dob),
            $this->formatDate($stockist->dom),
            $stockist->address,
            $stockist->dl_number,
            $stockist->gst_number,
            $stockist->msl_number,
        ];
    }

    private function stationType($stockist): string
    {
        if ($stockist->exstation_id) {
            return 'Ex-Station';
        }

        if ($stockist->outstation_id) {
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
