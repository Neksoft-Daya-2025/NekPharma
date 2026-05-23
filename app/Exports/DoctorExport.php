<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DoctorExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $doctors)
    {
    }

    public function collection(): Collection
    {
        return $this->doctors;
    }

    public function headings(): array
    {
        return [
            'Dr. Name',
            'HQ',
            'Area',
            'Ex-Station',
            'Outstation',
            'Station Type',
            'Qualification',
            'Dr. Type',
            'Speciality',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Address',
            'MSL Number',
            'Products',
        ];
    }

    public function map($doctor): array
    {
        return [
            $doctor->fullname,
            optional($doctor->headquarter)->name,
            optional($doctor->area)->name,
            optional($doctor->exstation)->name,
            optional($doctor->outstation)->name,
            $this->stationType($doctor),
            $doctor->qualification,
            $doctor->doctor_type,
            $doctor->speciality,
            $doctor->mobile,
            $doctor->email,
            $doctor->gender,
            $this->formatDate($doctor->dob),
            $this->formatDate($doctor->dom),
            $doctor->address,
            $doctor->msl_number,
            $doctor->products->pluck('name')->implode(', '),
        ];
    }

    private function stationType($doctor): string
    {
        if ($doctor->exstation_id) {
            return 'Ex-Station';
        }

        if ($doctor->outstation_id) {
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
