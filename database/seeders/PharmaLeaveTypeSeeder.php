<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

/**
 * Seeds default pharma leave types per SRS 3.1.2:
 * - Casual Leave (CL): 0.8 per month, pro-rata from joining, cannot take until confirmed (allowed_probation=0)
 * - Earned Leave (EL): 1.5 per month, pro-rata from joining, cannot take until confirmed (allowed_probation=0), included in F&F
 * - Sick Leave (SL): 1 per month, pro-rata from joining, everyone can take (allowed_probation=1)
 */
class PharmaLeaveTypeSeeder extends Seeder
{
    public function run($companyId)
    {
        $defaults = [
            [
                'type_name' => 'Casual Leave',
                'color' => '#ffc107',
                'leavetype' => 'monthly',
                'no_of_leaves' => 0.8,
                'monthly_limit' => 0,
                'paid' => 1,
                'allowed_probation' => 0,
                'allowed_notice' => 1,
            ],
            [
                'type_name' => 'Earned Leave',
                'color' => '#28a745',
                'leavetype' => 'monthly',
                'no_of_leaves' => 1.5,
                'monthly_limit' => 0,
                'paid' => 1,
                'allowed_probation' => 0,
                'allowed_notice' => 1,
            ],
            [
                'type_name' => 'Sick Leave',
                'color' => '#dc3545',
                'leavetype' => 'monthly',
                'no_of_leaves' => 1,
                'monthly_limit' => 0,
                'paid' => 1,
                'allowed_probation' => 1,
                'allowed_notice' => 1,
            ],
        ];

        foreach ($defaults as $row) {
            if (LeaveType::where('company_id', $companyId)->where('type_name', $row['type_name'])->exists()) {
                continue;
            }
            LeaveType::create(array_merge($row, [
                'company_id' => $companyId,
            ]));
        }
    }
}
