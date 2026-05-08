<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\Team;
use Illuminate\Database\Seeder;

class DepartmentTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($companyId)
    {

        $departments = [
            ['team_name' => 'Marketing', 'company_id' => $companyId],
            ['team_name' => 'Sales', 'company_id' => $companyId],
            ['team_name' => 'Human Resources', 'company_id' => $companyId],
            ['team_name' => 'Public Relations', 'company_id' => $companyId],
            ['team_name' => 'Research', 'company_id' => $companyId],
            ['team_name' => 'Finance', 'company_id' => $companyId],
        ];

        $designations = [
            ['name' => 'Trainee', 'company_id' => $companyId],
            ['name' => 'Senior', 'company_id' => $companyId],
            ['name' => 'Junior', 'company_id' => $companyId],
            ['name' => 'Team Lead', 'company_id' => $companyId],
            ['name' => 'Project Manager', 'company_id' => $companyId],
            // Requirement 2.1: Pharma hierarchy designations (bottom to top)
            ['name' => 'Medical Representative', 'company_id' => $companyId],
            ['name' => 'Area Business Manager (ABM)', 'company_id' => $companyId],
            ['name' => 'Regional Manager (RM)', 'company_id' => $companyId],
            ['name' => 'Zonal Manager (ZM)', 'company_id' => $companyId],
            ['name' => 'Sales Manager', 'company_id' => $companyId],
            ['name' => 'PMT', 'company_id' => $companyId],
            ['name' => 'HR', 'company_id' => $companyId],
            ['name' => 'Admin', 'company_id' => $companyId],
        ];

        Team::insert($departments);
        Designation::insert($designations);

        $teams = Team::where('company_id', $companyId)->pluck('id')->toArray();
        $designations = Designation::where('company_id', $companyId)->pluck('id')->toArray();

        LeaveType::where('company_id', $companyId)->update([
            'department' => json_encode($teams),
            'designation' => json_encode($designations),
        ]);

    }

}
