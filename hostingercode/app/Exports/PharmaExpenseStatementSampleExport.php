<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PharmaExpenseStatementSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $hq = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->orderBy('name')->first();
        $fromId = $hq && $hq->exstations->isNotEmpty() ? (string) $hq->exstations->first()->id : '101';
        $toId = $hq && $hq->outstations->isNotEmpty() ? (string) $hq->outstations->first()->id : '202';

        return [
            [
                now()->startOfMonth()->format('Y-m-d'),
                'Sample Town',
                'Medical Representative, ABM',
                3,
                2,
                $fromId,
                $toId,
                'Bike',
                12,
                50,
                0,
                200,
                0,
                0,
                0,
                'Sample day — replace with your data',
            ],
            [
                now()->startOfMonth()->addDay()->format('Y-m-d'),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
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
            'Date (Y-m-d; must be in selected month)',
            'Town Worked',
            'Worked With (comma-separated labels)',
            'No. of Doctors Met',
            'No. of Retailers Met',
            'Head Quarter From (station ID)',
            'Head Quarter To (station ID)',
            'Mode of Transport',
            'Km',
            'Fare Rs',
            'Daily Allowance HQ Rs',
            'Daily Allowance Ex Rs',
            'Daily Allowance O/S Rs',
            'Fixed Expenses',
            'Other Expenses',
            'Remarks',
        ];
    }
}
