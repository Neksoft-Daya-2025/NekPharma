<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeLeaveReportExport;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Scopes\ActiveScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class LeaveReportController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaveReport';
    }

    public function index()
    {
        $viewPermission = user()->permission('view_leave_report');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        return redirect()->route('leave-report.employee-leave-report');
    }

    public function show(Request $request, $id)
    {
        $viewPermission = user()->permission('view_leave_report');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        return redirect()->route('leave-report.employee-leave-report');
    }

    public function leaveQuota()
    {
        $viewPermission = user()->permission('view_leave_report');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        return redirect()->route('leave-report.employee-leave-report');
    }

    public function employeeLeaveQuota($id, $year, $month)
    {
        $forMontDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $thisMonthStartDate = now()->startOfMonth();

        $this->employee = User::with([
        'employeeDetail',
         'employeeDetail.designation',
         'employeeDetail.department',
         'country',
         'employee',
         'roles'
         ])
            ->onlyEmployee()
            ->when(!$thisMonthStartDate->eq($forMontDate), function($query) use($forMontDate) {
                $query->with([
                'leaveQuotaHistory' => function($query) use($forMontDate) {
                    $query->where('for_month', $forMontDate);
                },
                'leaveQuotaHistory.leaveType',
                ])->whereHas('leaveQuotaHistory', function($query) use($forMontDate) {
                    $query->where('for_month', $forMontDate);
                });
            })
        ->when($thisMonthStartDate->eq($forMontDate), function($query) {
            $query->with([
                'leaveTypes',
                'leaveTypes.leaveType',
            ]);
        })
        ->withoutGlobalScope(ActiveScope::class)
        ->findOrFail($id);


        $settings = company();
        $now = Carbon::now();
        $yearStartMonth = $settings->year_starts_from;
        $leaveStartDate = null;
        $leaveEndDate = null;

        if($settings && $settings->leaves_start_from == 'year_start'){

            if ($yearStartMonth > $now->month) {
                // Not completed a year yet
                $leaveStartDate = Carbon::create($now->year, $yearStartMonth, 1)->subYear();
                $leaveEndDate = $leaveStartDate->copy()->addYear()->subDay();

            } else {
                $leaveStartDate = Carbon::create($now->year, $yearStartMonth, 1);
                $leaveEndDate = $leaveStartDate->copy()->addYear()->subDay();
            }

        } elseif ($settings && $settings->leaves_start_from == 'joining_date'){

            $joiningDate = Carbon::parse($this->employee->employeedetails->joining_date->format((now(company()->timezone)->year) . '-m-d'));
            $joinMonth = $joiningDate->month;
            $joinDay = $joiningDate->day;

            if ($joinMonth > $now->month || ($joinMonth == $now->month && $now->day < $joinDay)) {
                // Not completed a year yet
                $leaveStartDate = $joiningDate->copy()->subYear();
                $leaveEndDate = $joiningDate->copy()->subDay();

            } else {
                // Completed a year
                $leaveStartDate = $joiningDate;
                $leaveEndDate = $joiningDate->copy()->addYear()->subDay();
            }

        }

        $this->employeeLeavesQuotas = $this->employee->leaveTypes;

        $hasLeaveQuotas = false;
        $totalLeaves = 0;
        $overUtilizedLeaves = 0;
        $leaveCounts = [];
        $allowedEmployeeLeavesQuotas = []; // Leave Types Which employee can take according to leave type conditions

        foreach ($this->employeeLeavesQuotas as $key => $leavesQuota) {

            if (
                ($leavesQuota->leaveType->deleted_at == null || $leavesQuota->leaves_used > 0) &&
                $leavesQuota->leaveType && ($leavesQuota->leaveType->leaveTypeCondition($leavesQuota->leaveType, $this->employee))) {

                $hasLeaveQuotas = true;
                $allowedEmployeeLeavesQuotas[] = $leavesQuota;

                // $sum = ($leavesQuota->leaveType->deleted_at == null) ? $leavesQuota->leaves_remaining : 0;
                // $totalLeaves = $totalLeaves + ($leavesQuota?->no_of_leaves ?: 0) - ($leaveCounts[$leavesQuota->leave_type_id] ?: 0);
                $totalLeaves = $totalLeaves + ($leavesQuota?->leaves_remaining ?: 0);
            }
        }
        
        $this->leaveCounts = $leaveCounts;
        $this->hasLeaveQuotas = $hasLeaveQuotas;
        $this->allowedEmployeeLeavesQuotas = $allowedEmployeeLeavesQuotas;
        $this->allowedLeaves = $totalLeaves + $overUtilizedLeaves; // remining leaves
    
        return view('reports.leave-quota.show', $this->data);
    }

    public function employeeLeaveReport()
    {
        $this->authorizeEmployeeLeaveReportAccess();
        
        $this->pageTitle = 'Employee Leave Report';
        
        // Get all employee IDs first
        $employeeIds = User::allLeaveReportEmployees(null, true)->pluck('id');
        
        // Get all employees with their leave types, designation, department, and headquarters
        $this->employees = User::whereIn('id', $employeeIds)
            ->with([
                'leaveTypes.leaveType',
                'employeeDetail.designation',
                'employeeDetail.department',
                'employeeDetail.headquarter'
            ])
            ->get();
        
        // Get all leave types
        $this->leaveTypes = LeaveType::where('company_id', company()->id)
            ->whereNull('deleted_at')
            ->get();
        
        // Prepare data for the report - only show employees who have leave quotas assigned
        $reportData = [];
        
        foreach ($this->employees as $employee) {
            // Only process employees who have at least one leave quota assigned
            if ($employee->leaveTypes->isEmpty()) {
                continue;
            }
            
            $employeeCode = $employee->employeeDetail->employee_id ?? '-';
            $designation = $employee->employeeDetail->designation->name ?? '-';
            $department = $employee->employeeDetail->department->team_name ?? '-';
            
            // Calculate totals for this employee
            $totalNoOfLeaves = 0;
            $totalLeavesTaken = 0;
            $totalRemainingLeaves = 0;
            
            foreach ($this->leaveTypes as $leaveType) {
                $leaveQuota = $employee->leaveTypes->where('leave_type_id', $leaveType->id)->first();
                
                // Only add row if employee has this leave type assigned (has quota)
                if ($leaveQuota && ($leaveQuota->no_of_leaves > 0 || $leaveQuota->leaves_used > 0)) {
                    $totalNoOfLeaves += $leaveQuota->no_of_leaves ?? 0;
                    $totalLeavesTaken += $leaveQuota->leaves_used ?? 0;
                    $totalRemainingLeaves += $leaveQuota->leaves_remaining ?? 0;
                    
                    $reportData[] = [
                        'employee_id' => $employee->id,
                        'employee_code' => $employeeCode,
                        'employee_name' => $employee->name,
                        'designation' => $designation,
                        'department' => $department,
                        'leave_type' => $leaveType->type_name,
                        'leave_type_color' => $leaveType->color,
                        'no_of_leaves' => $leaveQuota->no_of_leaves ?? 0,
                        'monthly_limit' => $leaveType->monthly_limit ?? 0,
                        'leaves_taken' => $leaveQuota->leaves_used ?? 0,
                        'remaining_leaves' => $leaveQuota->leaves_remaining ?? 0,
                        'over_utilized' => $leaveQuota->overutilised_leaves ?? 0,
                        'unused_leaves' => $leaveQuota->unused_leaves ?? 0,
                    ];
                }
            }
            
            // Add totals row if employee has leave quotas
            if (!empty($reportData) && end($reportData)['employee_id'] == $employee->id) {
                // Store totals in the last item's metadata (we'll use it in the view)
                $reportData[count($reportData) - 1]['_totals'] = [
                    'total_no_of_leaves' => $totalNoOfLeaves,
                    'total_leaves_taken' => $totalLeavesTaken,
                    'total_remaining_leaves' => $totalRemainingLeaves,
                ];
            }
        }
        
        $this->reportData = $reportData;
        
        return view('reports.employee-leave-report.index', $this->data);
    }

    public function exportEmployeeLeaveReport()
    {
        $this->authorizeEmployeeLeaveReportAccess();

        $payload = $this->buildEmployeeLeaveReportExportPayload();

        $fileName = 'Employee_Leave_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeLeaveReportExport($payload), $fileName);
    }

    /**
     * Build payload for Leave Formatt.xlsx–style grid (CL/SL/EL × monthly Leaves vs Availed).
     */
    private function buildEmployeeLeaveReportExportPayload(): array
    {
        $company = company();
        $tz = $company->timezone;
        $now = Carbon::now($tz);

        $leaveFrom = Carbon::createFromFormat('d-m-Y', '01-' . $company->year_starts_from . '-' . $now->year)->startOfMonth();
        if ($leaveFrom->isFuture()) {
            $leaveFrom->subYear();
        }
        $leaveTo = $leaveFrom->copy()->addYear()->subDay();

        $months = [];
        $cursor = $leaveFrom->copy();
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'label' => $cursor->format('M-y'),
                'start' => $cursor->copy()->startOfMonth(),
                'end' => $cursor->copy()->endOfMonth(),
            ];
            $cursor->addMonth();
        }

        $leaveTypes = LeaveType::where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->orderBy('type_name')
            ->get();

        $slots = $this->mapLeaveTypesToThreeSlots($leaveTypes);

        $employeeIds = User::allLeaveReportEmployees(null, true)->pluck('id');

        $employees = User::whereIn('id', $employeeIds)
            ->with([
                'leaveTypes.leaveType',
                'employeeDetail.designation',
                'employeeDetail.department',
            ])
            ->get();

        $allLeaves = Leave::whereIn('user_id', $employeeIds)
            ->where('status', 'approved')
            ->whereBetween('leave_date', [$leaveFrom->toDateString(), $leaveTo->toDateString()])
            ->get(['user_id', 'leave_type_id', 'leave_date', 'duration']);

        $availedMap = [];

        foreach ($allLeaves as $leave) {
            $d = Carbon::parse($leave->leave_date);
            $monthIdx = null;
            foreach ($months as $i => $m) {
                if ($d->between($m['start'], $m['end'])) {
                    $monthIdx = $i;
                    break;
                }
            }
            if ($monthIdx === null) {
                continue;
            }
            $days = ($leave->duration === 'half day') ? 0.5 : 1.0;
            $uid = $leave->user_id;
            $tid = $leave->leave_type_id;
            if (!isset($availedMap[$uid][$monthIdx])) {
                $availedMap[$uid][$monthIdx] = [];
            }
            $availedMap[$uid][$monthIdx][$tid] = ($availedMap[$uid][$monthIdx][$tid] ?? 0) + $days;
        }

        $rows = [];

        foreach ($employees as $employee) {
            if ($employee->leaveTypes->isEmpty()) {
                continue;
            }

            $detail = $employee->employeeDetail;
            $empCode = optional($detail)->employee_id ?? '-';
            $designation = optional(optional($detail)->designation)->name ?? '-';
            $department = optional(optional($detail)->department)->team_name ?? '-';
            $doj = ($detail && $detail->joining_date)
                ? Carbon::parse($detail->joining_date)->format('d-m-Y')
                : '-';

            $annual = [0, 0, 0];
            $perMonth = [0, 0, 0];

            foreach ([0, 1, 2] as $si) {
                $typeId = $slots[$si]['id'];
                if (!$typeId) {
                    continue;
                }
                $quota = $employee->leaveTypes->where('leave_type_id', $typeId)->first();
                $lt = $leaveTypes->firstWhere('id', $typeId);
                // Prorata is already in DB from RecalculateLeavesQuotas; export as whole days (integers)
                $no = $quota ? (float) ($quota->no_of_leaves ?? 0) : 0;
                $annual[$si] = $this->wholeLeaveDays($no);

                if ($lt && (float) ($lt->monthly_limit ?? 0) > 0) {
                    $perMonth[$si] = $this->wholeLeaveDays((float) $lt->monthly_limit);
                } else {
                    $perMonth[$si] = $no > 0 ? $this->wholeLeaveDays($no / 12) : 0;
                }
            }

            $monthlyBlocks = [];
            foreach ($months as $mi => $_) {
                $leaves = [0, 0, 0];
                $availed = [0, 0, 0];
                foreach ([0, 1, 2] as $si) {
                    $typeId = $slots[$si]['id'];
                    if (!$typeId) {
                        continue;
                    }
                    $leaves[$si] = $perMonth[$si];
                    $availed[$si] = $this->wholeLeaveDays((float) ($availedMap[$employee->id][$mi][$typeId] ?? 0));
                }
                $monthlyBlocks[] = [$leaves, $availed];
            }

            $rows[] = [
                'employee_code' => $empCode,
                'name' => $employee->name,
                'designation' => $designation,
                'department' => $department,
                'doj' => $doj,
                'annual' => $annual,
                'per_month_row' => $perMonth,
                'monthly' => $monthlyBlocks,
            ];
        }

        return [
            'leave_from' => $leaveFrom,
            'months' => $months,
            'slots' => $slots,
            'employees' => $rows,
        ];
    }

    /**
     * Map company leave types to three columns (CL / SL / EL) like the reference Excel.
     */
    private function mapLeaveTypesToThreeSlots(Collection $leaveTypes): array
    {
        $types = $leaveTypes->values();

        $findExact = function (string $code) use ($types) {
            foreach ($types as $t) {
                if (strtolower(trim($t->type_name)) === strtolower($code)) {
                    return $t;
                }
            }

            return null;
        };

        $findByPatterns = function (array $patterns) use ($types) {
            foreach ($types as $t) {
                $n = strtolower($t->type_name);
                foreach ($patterns as $p) {
                    if (str_contains($n, $p)) {
                        return $t;
                    }
                }
            }

            return null;
        };

        $cl = $findByPatterns(['casual leave', 'casual']) ?? $findExact('cl');
        $sl = $findByPatterns(['sick leave', 'sick']) ?? $findExact('sl');
        $el = $findByPatterns(['earned leave', 'earned']) ?? $findExact('el');

        $slots = [
            ['id' => null, 'label' => 'CL'],
            ['id' => null, 'label' => 'SL'],
            ['id' => null, 'label' => 'EL'],
        ];

        $picked = [];

        $tryAssign = function ($t, int $idx) use (&$slots, &$picked) {
            if ($t && !isset($picked[$t->id])) {
                $slots[$idx]['id'] = $t->id;
                $picked[$t->id] = true;
            }
        };

        $tryAssign($cl, 0);
        $tryAssign($sl, 1);
        $tryAssign($el, 2);

        foreach ($types as $t) {
            if (isset($picked[$t->id])) {
                continue;
            }
            for ($i = 0; $i < 3; $i++) {
                if ($slots[$i]['id'] === null) {
                    $slots[$i]['id'] = $t->id;
                    $slots[$i]['label'] = strtoupper(substr($t->type_name, 0, 3));
                    $picked[$t->id] = true;
                    break;
                }
            }
        }

        return $slots;
    }

    /**
     * Whole leave days for Excel export (matches integer-style display elsewhere, e.g. remaining leaves).
     */
    private function wholeLeaveDays(float $value): int
    {
        return (int) round($value);
    }

    /**
     * Legacy leave reports used view_leave_report; HR menu uses view_leave — allow either.
     */
    private function authorizeEmployeeLeaveReportAccess(): void
    {
        $viewLeaveReport = user()->permission('view_leave_report');
        $viewLeave = user()->permission('view_leave');
        $canReport = in_array($viewLeaveReport, ['all', 'added', 'owned', 'both'], true);
        $canLeave = in_array($viewLeave, ['all', 'added', 'owned', 'both'], true);
        abort_403(!$canReport && !$canLeave);
    }

}
