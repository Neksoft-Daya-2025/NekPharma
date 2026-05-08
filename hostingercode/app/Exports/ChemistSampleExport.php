<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ChemistSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // Get actual headquarters and stations from database for sample data
        $headquarters = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->orderBy('name')->take(3)->get();
        $hq1 = $headquarters[0] ?? null;
        $hq2 = $headquarters[1] ?? null;
        $hq3 = $headquarters[2] ?? null;
        
        // Get ex-stations and out-stations for sample using relationships
        $hq1Exstation = $hq1 && $hq1->exstations->isNotEmpty() ? $hq1->exstations->first() : null;
        $hq2Outstation = $hq2 && $hq2->outstations->isNotEmpty() ? $hq2->outstations->first() : null;
        
        return [
            [
                'ABC Pharmacy',                          // Shop Name (mandatory)
                $hq1 ? $hq1->name : 'Lucknow 1',        // Headquarter (mandatory)
                'headquarter',                           // Station Type (mandatory)
                '',                                      // Station Name (mandatory - empty for headquarter)
                '123 Main Street, City, State',         // Address (mandatory)
                'Rajesh Kumar',                          // Chemist Name (optional)
                '9876543210',                            // Mobile (optional)
                'abc.pharmacy@example.com',              // Email (optional)
                'Male',                                  // Gender (optional)
                '01-01-1980',                            // Date of Birth (optional)
                '15-06-2005'                             // Date of Marriage (optional)
            ],
            [
                'XYZ Medical Store',                     // Shop Name (mandatory)
                $hq1 ? $hq1->name : 'Lucknow 1',        // Headquarter (mandatory) - same HQ as exstation
                'exstation',                             // Station Type (mandatory)
                $hq1Exstation ? $hq1Exstation->name : 'Sample Ex-Station', // Station Name (mandatory)
                '456 Park Avenue, City, State',          // Address (mandatory)
                'Priya Sharma',                          // Chemist Name (optional)
                '9876543211',                            // Mobile (optional)
                'xyz.medical@example.com',               // Email (optional)
                'Female',                                // Gender (optional)
                '15-03-1985',                            // Date of Birth (optional)
                ''                                       // Date of Marriage (optional)
            ],
            [
                'PQR Drug Mart',                         // Shop Name (mandatory)
                $hq2 ? $hq2->name : 'Gonda',           // Headquarter (mandatory) - same HQ as outstation
                'outstation',                            // Station Type (mandatory)
                $hq2Outstation ? $hq2Outstation->name : 'Sample Out-Station', // Station Name (mandatory)
                '789 Market Road, City, State',          // Address (mandatory)
                'Amit Verma',                            // Chemist Name (optional)
                '9876543212',                            // Mobile (optional)
                'pqr.drugmart@example.com',               // Email (optional)
                'Male',                                  // Gender (optional)
                '20-05-1990',                            // Date of Birth (optional)
                ''                                       // Date of Marriage (optional)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Shop Name',                                 // Mandatory
            'Headquarter',                               // Mandatory
            'Station Type (headquarter/exstation/outstation)', // Mandatory
            'Station Name (Ex-Station or Out-Station)', // Mandatory
            'Address',                                   // Mandatory
            'Chemist Name',                              // Optional
            'Mobile',                                    // Optional
            'Email',                                     // Optional
            'Gender',                                    // Optional
            'Date of Birth',                             // Optional
            'Date of Marriage'                           // Optional
        ];
    }
}

