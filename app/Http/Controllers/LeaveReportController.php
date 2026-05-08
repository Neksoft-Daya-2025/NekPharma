<?php

namespace App\Http\Controllers;

use App\Console\Commands\RecalculateLeavesQuotas;
use App\Exports\EmployeeLeaveReportTableExport;
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
        $c = company();
        $this->leavesStartFrom = $c->leaves_start_from;
        $this->yearStartsFrom = (int) ($c->year_starts_from ?? 1);
        $this->fiscalMonthName = Carbon::createFromDate(2000, $this->yearStartsFrom, 1)->format('F');
        $this->reportData = $this->buildEmployeeLeaveReportRows();

        return view('reports.employee-leave-report.index', $this->data);
    }

    public function exportEmployeeLeaveReport()
    {
        $this->authorizeEmployeeLeaveReportAccess();

        $reportData = $this->buildEmployeeLeaveReportRows();
        $exportRows = $this->mapEmployeeLeaveReportDataForExport($reportData);

        $fileName = 'Employee_Leave_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeLeaveReportTableExport($exportRows), $fileName);
    }

    /**
     * Same table rows as the on-screen report: quota fields from DB, filtered by
     * LeaveType::leaveTypeCondition (probation, notice, department, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeeLeaveReportRows(): array
    {
        $employeeIds = User::allLeaveReportEmployees(null, true)->pluck('id');

        $employees = User::whereIn('id', $employeeIds)
            ->with([
                'employee',
                'leaveTypes.leaveType',
                'employeeDetail.designation',
                'employeeDetail.department',
                'employeeDetail.headquarter',
                'roles',
            ])
            ->get();

        $leaveTypes = LeaveType::where('company_id', company()->id)
            ->whereNull('deleted_at')
            ->get();

        $reportData = [];

        foreach ($employees as $employee) {
            if ($employee->leaveTypes->isEmpty()) {
                continue;
            }

            $employeeCode = $employee->employeeDetail->employee_id ?? '-';
            $designation = $employee->employeeDetail->designation->name ?? '-';
            $department = $employee->employeeDetail->department->team_name ?? '-';
            $joiningCarbon = $employee->employeeDetail?->joining_date
                ? Carbon::parse($employee->employeeDetail->joining_date)
                : null;
            $joiningDisplay = $joiningCarbon
                ? $joiningCarbon->format(company()->date_format)
                : '-';

            foreach ($leaveTypes as $leaveType) {
                $leaveQuota = $employee->leaveTypes->where('leave_type_id', $leaveType->id)->first();

                if (
                    ! $leaveQuota
                    || (($leaveQuota->no_of_leaves <= 0) && ($leaveQuota->leaves_used <= 0))
                    || ! $leaveType->leaveTypeCondition($leaveType, $employee)
                ) {
                    continue;
                }

                $monthsInCycle = null;
                if ($joiningCarbon && ! (int) ($leaveQuota->leave_type_impact ?? 0)) {
                    $monthsInCycle = RecalculateLeavesQuotas::proRataMonthCountAndYearStart(
                        company(),
                        $joiningCarbon,
                        $employee,
                        $leaveType
                    )[0];
                }

                $reportData[] = [
                    'employee_id' => $employee->id,
                    'employee_code' => $employeeCode,
                    'employee_name' => $employee->name,
                    'designation' => $designation,
                    'department' => $department,
                    'joining_date' => $joiningDisplay,
                    'leave_type' => $leaveType->type_name,
                    'leave_type_color' => $leaveType->color,
                    /** Same "months counted" as app:recalculate-leaves-quotas (from join month in joining_date mode). */
                    'months_in_cycle' => $monthsInCycle,
                    'quota_manual' => (int) ($leaveQuota->leave_type_impact ?? 0) === 1,
                    /** Cumulative after recalc. */
                    'no_of_leaves' => $leaveQuota->no_of_leaves ?? 0,
                    'per_month_pro_rata' => $this->perMonthProRataFromPolicy($leaveType),
                    'monthly_limit' => $leaveType->monthly_limit ?? 0,
                    'leaves_taken' => $leaveQuota->leaves_used ?? 0,
                    'remaining_leaves' => $leaveQuota->leaves_remaining ?? 0,
                    'over_utilized' => $leaveQuota->overutilised_leaves ?? 0,
                    'unused_leaves' => $leaveQuota->unused_leaves ?? 0,
                ];
            }
        }

        return $reportData;
    }

    /**
     * @param  array<int, array<string, mixed>>  $reportData
     */
    private function mapEmployeeLeaveReportDataForExport(array $reportData): Collection
    {
        $rows = collect();

        foreach ($reportData as $row) {
            $monthly = ($row['monthly_limit'] ?? 0) > 0
                ? $row['monthly_limit']
                : '--';

            $perMonth = $row['per_month_pro_rata'] ?? null;
            $perMonthDisplay = $perMonth === null ? '--' : rtrim(rtrim(number_format((float) $perMonth, 2, '.', ''), '0'), '.');

            if (! empty($row['quota_manual'])) {
                $monthsDisplay = 'manual quota';
            } else {
                $m = $row['months_in_cycle'] ?? null;
                $monthsDisplay = $m === null ? '--' : (string) $m;
            }

            $rows->push([
                $row['employee_code'],
                $row['employee_name'],
                $row['designation'],
                $row['department'],
                $row['joining_date'] ?? '-',
                $row['leave_type'],
                $perMonthDisplay,
                $monthsDisplay,
                $row['no_of_leaves'],
                $monthly,
                $row['leaves_taken'],
                $row['remaining_leaves'],
                $row['over_utilized'],
                $row['unused_leaves'],
            ]);
        }

        return $rows;
    }

    /**
     * Policy monthly pro-rata rate from leave type: yearly = annual ÷ 12; monthly = per-month amount in settings.
     */
    private function perMonthProRataFromPolicy(LeaveType $leaveType): float
    {
        $n = (float) ($leaveType->no_of_leaves ?? 0);

        if ($leaveType->leavetype === 'yearly') {
            return $n / 12;
        }

        return $n;
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
