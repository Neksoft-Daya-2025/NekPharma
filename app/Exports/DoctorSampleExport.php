<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DoctorSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Get actual headquarters and stations from database for sample data
        $headquarters = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->orderBy('name')->take(3)->get();
        $hq1 = $headquarters[0] ?? null;
        $hq2 = $headquarters[1] ?? null;
        $hq3 = $headquarters[2] ?? null;
        
        // Get ex-stations and out-stations for sample using relationships
        // For exstation example, use hq1's exstation
        $hq1Exstation = $hq1 && $hq1->exstations->isNotEmpty() ? $hq1->exstations->first() : null;
        // For outstation example, use hq2's outstation
        $hq2Outstation = $hq2 && $hq2->outstations->isNotEmpty() ? $hq2->outstations->first() : null;
        
        return [
            [
                'Dr. John Smith',                    // Full Name (mandatory)
                'MBBS, MD',                          // Qualification (mandatory)
                $hq1 ? $hq1->name : 'Lucknow 1',    // Headquarter (mandatory)
                'headquarter',                       // Station Type (mandatory)
                '',                                  // Station Name (mandatory - empty for headquarter)
                '123 Main Street, City, State',      // Address (mandatory)
                'Cardiology',                        // Speciality (mandatory)
                '9876543210',                        // Mobile (optional)
                'john.smith@example.com',            // Email (optional)
                'Male',                              // Gender (optional)
                '01-01-1980',                        // Date of Birth (optional)
                '15-06-2005',                        // Date of Marriage (optional)
                'VIP',                               // Doctor Type (optional)
                'Paracetamol, Amoxicillin'           // Products (optional)
            ],
            [
                'Dr. Jane Doe',                      // Full Name (mandatory)
                'MBBS, MS',                          // Qualification (mandatory)
                $hq1 ? $hq1->name : 'Lucknow 1',     // Headquarter (mandatory) - same HQ as exstation
                'exstation',                         // Station Type (mandatory)
                $hq1Exstation ? $hq1Exstation->name : 'Sample Ex-Station', // Station Name (mandatory)
                '456 Park Avenue, City, State',       // Address (mandatory)
                'Orthopedics',                       // Speciality (mandatory)
                '9876543211',                        // Mobile (optional)
                'jane.doe@example.com',              // Email (optional)
                'Female',                            // Gender (optional)
                '15-03-1985',                        // Date of Birth (optional)
                '',                                  // Date of Marriage (optional)
                'CORE',                              // Doctor Type (optional)
                'Cough Syrup, Vitamin D3'            // Products (optional)
            ],
            [
                'Dr. Robert Johnson',                // Full Name (mandatory)
                'MBBS',                             // Qualification (mandatory)
                $hq2 ? $hq2->name : 'Gonda',         // Headquarter (mandatory) - same HQ as outstation
                'outstation',                        // Station Type (mandatory)
                $hq2Outstation ? $hq2Outstation->name : 'Sample Out-Station', // Station Name (mandatory)
                '789 Market Road, City, State',      // Address (mandatory)
                'General Medicine',                  // Speciality (mandatory)
                '9876543212',                        // Mobile (optional)
                'robert.johnson@example.com',        // Email (optional)
                'Male',                              // Gender (optional)
                '20-05-1990',                        // Date of Birth (optional)
                '',                                  // Date of Marriage (optional)
                '',                                  // Doctor Type (optional)
                ''                                   // Products (optional)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Full Name',                             // Mandatory
            'Qualification',                         // Mandatory
            'Headquarter',                           // Mandatory
            'Station Type (headquarter/exstation/outstation)', // Mandatory
            'Station Name (Ex-Station or Out-Station)', // Mandatory
            'Address',                               // Mandatory
            'Speciality',                            // Mandatory
            'Mobile',                                // Optional
            'Email',                                 // Optional
            'Gender',                                // Optional
            'Date of Birth',                         // Optional
            'Date of Marriage',                      // Optional
            'Doctor Type (SFC)',                     // Optional
            'Products (comma-separated)'             // Optional
        ];
    }
}

