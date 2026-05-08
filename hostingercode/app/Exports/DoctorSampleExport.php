<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Column order matches App\Imports\DoctorImport::fields() for positional (no-heading) imports.
 */
class DoctorSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $headquarters = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->orderBy('name')->take(3)->get();
        $hq1 = $headquarters[0] ?? null;
        $hq2 = $headquarters[1] ?? null;
        $hq1Exstation = $hq1 && $hq1->exstations->isNotEmpty() ? $hq1->exstations->first() : null;
        $hq2Outstation = $hq2 && $hq2->outstations->isNotEmpty() ? $hq2->outstations->first() : null;

        // Order: fullname, headquarter, station, doctor_type, qualification, station_type, address, speciality, mobile, email, gender, dob, dom, products, products_2, products_3, msl_number
        return [
            [
                'Dr. John Smith',
                $hq1 ? $hq1->name : 'Lucknow 1',
                '',
                'VIP',
                'MBBS, MD',
                'headquarter',
                '123 Main Street, City, State',
                'Cardiology',
                '9876543210',
                'john.smith@example.com',
                'Male',
                '01-01-1980',
                '15-06-2005',
                'Paracetamol, Amoxicillin',
                '',
                '',
                '',
            ],
            [
                'Dr. Jane Doe',
                $hq1 ? $hq1->name : 'Lucknow 1',
                $hq1Exstation ? $hq1Exstation->name : 'Sample Ex-Station',
                'CORE',
                'MBBS, MS',
                'exstation',
                '456 Park Avenue, City, State',
                'Orthopedics',
                '9876543211',
                'jane.doe@example.com',
                'Female',
                '15-03-1985',
                '',
                'Cough Syrup, Vitamin D3',
                '',
                '',
                '',
            ],
            [
                'Dr. Robert Johnson',
                $hq2 ? $hq2->name : 'Gonda',
                $hq2Outstation ? $hq2Outstation->name : 'Sample Out-Station',
                '',
                'MBBS',
                'outstation',
                '789 Market Road, City, State',
                'General Medicine',
                '9876543212',
                'robert.johnson@example.com',
                'Male',
                '20-05-1990',
                '',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Dr. Name',
            'HQ',
            'Station Name',
            'Dr. Type (SFC)',
            'Qualification',
            'Station Type',
            'Address',
            'Speciality',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Products (comma-separated)',
            'Brand 2',
            'Brand 3',
            'MSL Number',
        ];
    }
}
