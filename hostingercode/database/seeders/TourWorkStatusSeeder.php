<?php

namespace Database\Seeders;

use App\Models\TourWorkStatus;
use Illuminate\Database\Seeder;

class TourWorkStatusSeeder extends Seeder
{
    /**
     * Run the database seeds for Tour Work Statuses.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Area Conference', 'color' => '#2196F3'],
            ['name' => 'Meeting', 'color' => '#4CAF50'],
            ['name' => 'OPD Camp', 'color' => '#FF9800'],
            ['name' => 'Working Days', 'color' => '#8bab4c'],
            ['name' => 'Sunday', 'color' => '#F44336'],
            ['name' => 'Holiday', 'color' => '#9C27B0'],
            ['name' => 'Leave', 'color' => '#607D8B'],
        ];

        foreach ($statuses as $status) {
            TourWorkStatus::updateOrCreate(
                ['company_id' => 1, 'name' => $status['name']],
                [
                    'color' => $status['color'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Tour work statuses seeded successfully!');
    }
}
