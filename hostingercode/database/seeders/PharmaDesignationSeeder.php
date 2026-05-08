<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Seeder;

/**
 * Requirement 2.1 (optional): Add pharma hierarchy designations for existing companies that don't have them.
 */
class PharmaDesignationSeeder extends Seeder
{
    protected array $pharmaDesignationNames = [
        'Medical Representative',
        'Area Business Manager (ABM)',
        'Regional Manager (RM)',
        'Zonal Manager (ZM)',
        'Sales Manager',
        'MIS Executive',
        'PMT',
        'HR',
        'Admin',
    ];

    public function run(): void
    {
        foreach (Company::all() as $company) {
            $existing = Designation::where('company_id', $company->id)
                ->whereIn('name', $this->pharmaDesignationNames)
                ->pluck('name')
                ->toArray();
            $toAdd = array_diff($this->pharmaDesignationNames, $existing);
            foreach ($toAdd as $name) {
                Designation::create(['name' => $name, 'company_id' => $company->id]);
            }
        }
        $this->command->info('Pharma designations backfill completed.');
    }
}
