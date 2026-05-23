<?php

namespace App\Http\Controllers;

use App\Helper\Common;
use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\DcrReport;
use App\Models\DcrDoctorVisit;
use App\Models\DcrChemistVisit;
use App\Models\DcrStockistVisit;
use App\Models\Doctor;
use App\Models\Chemist;
use App\Models\Stockist;
use App\Models\Product;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaExstation;
use App\Models\PharmaOutstation;
use App\Models\PharmaArea;
use App\Models\PharmaRegion;
use App\Models\User;
use App\Models\Attendance;
use App\Models\DcrTourSyncLog;
use App\Models\Tour;
use App\Models\TourMonthLock;
use App\Models\PharmaHeadquarterAssign;
use App\Notifications\DcrSubmitted;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\AccessibleHeadquarters;
use App\Support\EnterpriseAudit;

class DcrReportController extends AccountBaseController
{
    use AccessibleHeadquarters;
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'DCR Reports';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('dcr_reports', $this->user->modules));
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $this->viewPermission = user()->permission('view_dcr_reports');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $mode = $request->get('mode');
        $selectedEmployeeId = $request->get('employee_id');
        $this->selectedEmployeeId = $selectedEmployeeId;

        // APPROVAL PAGE: Show DCR reports submitted TO the current user for approval
        if ($mode === 'approve') {
            $this->approvePermission = user()->permission('approve_dcr_reports');
            $approvePerm = $this->approvePermission;
            $hasApprovePermission = in_array($approvePerm, ['all', 'added', 'owned', 'both'], true);
            $hasDcrsSubmittedToMe = DcrReport::where('submitted_to', user()->id)
                ->where('company_id', company()->id)
                ->exists();
            $reportingDescendantIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
            $hasReportingEmployees = $reportingDescendantIds !== [];
            $hasReportingEmployeesWithDcrs = false;
            if ($hasReportingEmployees) {
                $hasReportingEmployeesWithDcrs = DcrReport::whereIn('user_id', $reportingDescendantIds)
                    ->where('company_id', company()->id)
                    ->exists();
            }
            $canAccessApprovePage = $hasApprovePermission
                || $hasDcrsSubmittedToMe
                || $hasReportingEmployees
                || $hasReportingEmployeesWithDcrs;
            abort_403(! $canAccessApprovePage);

            $this->reportingDescendantUserIds = $reportingDescendantIds;

            // Load employees with their headquarter and designation information
            if ($this->viewPermission == 'all') {
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter'])
                    ->whereHas('employeeDetail')
                    ->where('company_id', company()->id)
                    ->when(!empty($viewableIds), fn ($q) => $q->whereIn('id', $viewableIds))
                    ->orderBy('name')
                    ->get()
                    ->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'name' => $employee->name,
                            'designation' => optional($employee->employeeDetail)->designation->name ?? null,
                            'headquarter_id' => optional($employee->employeeDetail)->headquarter_id,
                            'headquarter_name' => optional($employee->employeeDetail->headquarter)->name ?? null,
                        ];
                    });
            } else {
                // Non-admin: employees who submitted DCRs to current user OR any descendant in reporting tree
                $reportingEmployeeIds = $this->reportingDescendantUserIds;

                // Get employee IDs from DCRs submitted to current user
                $submittedEmployeeIds = DcrReport::where('submitted_to', user()->id)
                    ->where('company_id', company()->id)
                    ->distinct()
                    ->pluck('user_id')
                    ->toArray();
                
                // Combine both: employees who submitted to user + employees who report to user
                $employeeIds = array_unique(array_merge($submittedEmployeeIds, $reportingEmployeeIds));
                // Requirement 2.2: only include employees the current user can view by hierarchy
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $employeeIds = array_values(array_intersect($employeeIds, $viewableIds));
                
                if (!empty($employeeIds)) {
                    $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter'])
                        ->whereHas('employeeDetail')
                        ->whereIn('id', $employeeIds)
                        ->where('company_id', company()->id)
                        ->orderBy('name')
                        ->get()
                        ->map(function($employee) {
                            return [
                                'id' => $employee->id,
                                'name' => $employee->name,
                                'designation' => optional($employee->employeeDetail)->designation->name ?? null,
                                'headquarter_id' => optional($employee->employeeDetail)->headquarter_id,
                                'headquarter_name' => optional($employee->employeeDetail->headquarter)->name ?? null,
                            ];
                        });
                } else {
                    $this->employees = collect();
                }
            }

            // Load DCR reports submitted TO the current user for approval
            $dcrQuery = DcrReport::with([
                'user.employeeDetail.designation',
                'user.employeeDetails.designation',
                'user.employeeDetail.headquarter',
                'user.employeeDetails.headquarter',
                'submittedTo',
                'approvedBy',
                'doctorVisits.doctor',
                'chemistVisits.chemist',
                'stockistVisits.stockist'
            ]);

            // Get accessible area IDs for filtering DCR reports by area
            $accessibleAreaIds = $this->accessibleAreaIds();
            $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();

            if ($this->viewPermission == 'all') {
                // Admin / view-all: restrict by hierarchy; non-admin also by HQ scope
                $dcrQuery = $dcrQuery->where('company_id', company()->id);
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                if (!empty($viewableIds)) {
                    $dcrQuery = $dcrQuery->whereIn('user_id', $viewableIds);
                }
                if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                    $dcrQuery = $dcrQuery->where('user_id', $selectedEmployeeId);
                }
                // Non-admin with 'all' permission: still restrict by accessible HQs (Requirement 3.1.1)
                if (!user()->hasAdminLikeAccess() && $accessibleHqIdsForFilter !== null) {
                    if (!empty($accessibleHqIdsForFilter)) {
                        $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                            $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                        });
                    } else {
                        $dcrQuery->whereRaw('1 = 0');
                    }
                }
            } else {
                // Non-admin: DCRs submitted to me, or from any descendant in the reporting tree (transitive)
                $reportingEmployeeIds = $this->reportingDescendantUserIds;
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

                $dcrQuery = $dcrQuery->where(function($q) use ($reportingEmployeeIds) {
                    $q->where('submitted_to', user()->id);

                    if (! empty($reportingEmployeeIds)) {
                        $q->orWhereIn('user_id', $reportingEmployeeIds);
                    }
                })
                ->where('company_id', company()->id);
                
                // Filter by accessible headquarters for ABM profiles
                // DCR reports are filtered by the creator's headquarter
                if ($accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
                    $dcrQuery->whereHas('user.employeeDetail.headquarter', function($hqQuery) use ($accessibleHqIdsForFilter) {
                        $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                    });
                } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                    // No accessible headquarters - return empty
                    $dcrQuery->whereRaw('1 = 0');
                }
                
                // If employee filter is set, filter by that employee
                if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                    $dcrQuery = $dcrQuery->where('user_id', $selectedEmployeeId);
                }
            }

            $this->reports = $dcrQuery->orderBy('report_date', 'desc')->get();
            $this->pageTitle = 'Approve DCR Reports';
            
            // Check if user has any DCRs submitted to them (to show/hide menu item)
            $this->hasDcrsToApprove = DcrReport::where('submitted_to', user()->id)
                ->where('company_id', company()->id)
                ->exists();

            return view('dcr-reports.approve', $this->data);
        }

        // STATUS PAGE: Show DCR reports for viewing status
        $dcrQuery = DcrReport::with([
            'user.employeeDetail.designation',
            'user.employeeDetails.designation',
            'user.employeeDetail.headquarter.area',
            'user.employeeDetail.headquarter.area.region',
            'user.employeeDetail.headquarter',
            'user.employeeDetails.headquarter',
            'submittedTo.employeeDetail.designation',
            'submittedTo.employeeDetails.designation',
            'approvedBy',
            'doctorVisits.doctor',
            'chemistVisits.chemist',
            'stockistVisits.stockist'
        ]);

        // Get accessible area IDs for filtering DCR reports by area
        $accessibleAreaIds = $this->accessibleAreaIds();
        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();

        if ($this->viewPermission == 'all') {
            $dcrQuery = $dcrQuery->where('company_id', company()->id);
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            if (!empty($viewableIds)) {
                $dcrQuery = $dcrQuery->whereIn('user_id', $viewableIds);
            }
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $dcrQuery = $dcrQuery->where('user_id', $selectedEmployeeId);
            }
            // Non-admin with 'all' permission: still restrict by accessible HQs (Requirement 3.1.1)
            if (!user()->hasAdminLikeAccess() && $accessibleHqIdsForFilter !== null) {
                if (!empty($accessibleHqIdsForFilter)) {
                    $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                        $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                    });
                } else {
                    $dcrQuery->whereRaw('1 = 0');
                }
            }
        } else {
            // Non-admin: Show DCRs created by current user OR DCRs submitted to current user
            $dcrQuery = $dcrQuery->where(function($q) {
                $q->where('user_id', user()->id)
                  ->orWhere('submitted_to', user()->id);
            });
            
            // Also include DCRs from the full reporting subtree (transitive)
            $reportingEmployeeIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

            if (! empty($reportingEmployeeIds)) {
                $dcrQuery = $dcrQuery->orWhere(function($q) use ($reportingEmployeeIds) {
                    $q->whereIn('user_id', $reportingEmployeeIds);
                });
            }
            
            // Filter by accessible headquarters for ABM profiles
            // DCR reports are filtered by the creator's headquarter
            if ($accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
                $dcrQuery->whereHas('user.employeeDetail.headquarter', function($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                // No accessible headquarters - return empty
                $dcrQuery->whereRaw('1 = 0');
            }
        }
        
        // Get date filters from request
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        
        // Apply date filters to query before getting results
        if ($fromDate) {
            $dcrQuery->whereDate('report_date', '>=', $fromDate);
        }
        
        if ($toDate) {
            $dcrQuery->whereDate('report_date', '<=', $toDate);
        }

        $this->reports = $dcrQuery->orderBy('report_date', 'desc')->get();
        $this->pageTitle = 'DCR Reports';
        
        // Filter headquarters based on employee assignment and area mapping
        // For ABM/Area Business Manager: show all headquarters mapped to their assigned areas
        // For admin: show all headquarters
        // For non-admin: show headquarters based on their areas
        $headquarterQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id);
        
        // Determine which employee to check
        $employeeToCheck = null;
        
        if ($this->viewPermission == 'all') {
            // Admin user: show all headquarters (no filtering)
            // No need to set $employeeToCheck
        } else {
            // Non-admin: check current user's areas
            $employeeToCheck = user();
        }
        
        // If we have an employee to check, filter by their accessible headquarters
        if ($employeeToCheck) {
            // Use the AccessibleHeadquarters trait method to get correct headquarters
            $accessibleHqIds = $this->accessibleHeadquarterIds($employeeToCheck);
            
            if ($accessibleHqIds === null) {
                // Admin or no restrictions - show all headquarters
                // No filtering needed
            } elseif (empty($accessibleHqIds)) {
                // No accessible headquarters - return empty
                $headquarterQuery = $headquarterQuery->where('id', 0);
            } else {
                // Filter by accessible headquarters (respects pharma_assign_headquarters table)
                $headquarterQuery = $headquarterQuery->whereIn('id', $accessibleHqIds);
            }
        }
        // If admin (no employee to check), show all - no filtering
        
        $this->headquarters = $headquarterQuery->orderBy('name')->get();

        // Areas and regions for Area-wise / Region-wise filters (from HQs' areas)
        $areaIds = $this->headquarters->pluck('area_id')->filter()->unique()->values();
        $this->areas = $areaIds->isNotEmpty()
            ? PharmaArea::whereIn('id', $areaIds)->where('company_id', company()->id)->orderBy('name')->get()
            : collect();
        $regionIds = $this->areas->pluck('region_id')->filter()->unique()->values();
        $this->regions = $regionIds->isNotEmpty()
            ? PharmaRegion::whereIn('id', $regionIds)->where('company_id', company()->id)->orderBy('name')->get()
            : collect();
        
        // Get selected HQ, Area, Region from request
        $selectedHQ = $request->get('hq');
        $this->selectedHQ = $selectedHQ;
        $selectedArea = $request->get('area');
        $this->selectedArea = $selectedArea;
        $selectedRegion = $request->get('region');
        $this->selectedRegion = $selectedRegion;
        
        // Filter reports by HQ if selected (after date filtering)
        if ($selectedHQ) {
            $this->reports = $this->reports->filter(function($report) use ($selectedHQ) {
                $hqName = $report->headquarter;
                if ($hqName) {
                    $hq = $this->headquarters->firstWhere('id', $selectedHQ);
                    return $hq && $hq->name === $hqName;
                }
                return false;
            });
        }

        // Filter by Area (report's user's headquarter's area_id)
        if ($selectedArea) {
            $this->reports = $this->reports->filter(function($report) use ($selectedArea) {
                $areaId = optional(optional(optional($report->user)->employeeDetail)->headquarter)->area_id;
                return $areaId != null && (int) $areaId === (int) $selectedArea;
            });
        }

        // Filter by Region (report's user's headquarter's area's region_id)
        if ($selectedRegion) {
            $this->reports = $this->reports->filter(function($report) use ($selectedRegion) {
                $regionId = optional(optional(optional(optional($report->user)->employeeDetail)->headquarter)->area)->region_id;
                return $regionId != null && (int) $regionId === (int) $selectedRegion;
            });
        }

        $this->dcrDraftResumeInfo = $this->buildDcrDraftResumeInfoFromPayload(null);
        if (in_array(user()->permission('add_dcr_reports'), ['all', 'added'], true)) {
            $draftRow = $this->findLatestDraftDcrForUser(user()->id);
            if ($draftRow) {
                $this->dcrDraftResumeInfo = $this->buildDcrDraftResumeInfoFromPayload($this->serializeDraftForView($draftRow));
            }
        }

        return view('dcr-reports.index', $this->data);
    }

    /**
     * Call average analysis: average doctor/chemist/stockist calls per working day per employee.
     */
    public function callAverage(Request $request)
    {
        $this->viewPermission = user()->permission('view_dcr_reports');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $employeeId = $request->get('employee_id');
        $selectedHQ = $request->get('hq');
        $selectedArea = $request->get('area');
        $selectedRegion = $request->get('region');

        $dcrQuery = DcrReport::with([
            'user.employeeDetail.designation',
            'user.employeeDetail.headquarter.area',
            'user.employeeDetail.headquarter.area.region',
        ])
            ->withCount(['doctorVisits', 'chemistVisits', 'stockistVisits'])
            ->where('company_id', company()->id);

        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();
        if ($this->viewPermission == 'all') {
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            if (!empty($viewableIds)) {
                $dcrQuery->whereIn('user_id', $viewableIds);
            }
            if ($employeeId && $employeeId != 'all') {
                $dcrQuery->where('user_id', $employeeId);
            }
            if (!user()->hasAdminLikeAccess() && $accessibleHqIdsForFilter !== null) {
                if (!empty($accessibleHqIdsForFilter)) {
                    $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                        $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                    });
                } else {
                    $dcrQuery->whereRaw('1 = 0');
                }
            }
        } else {
            $reportingEmployeeIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
            $dcrQuery->where(function ($q) use ($reportingEmployeeIds) {
                $q->where('user_id', user()->id);
                if (! empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
            if ($employeeId && $employeeId != 'all') {
                $dcrQuery->where('user_id', $employeeId);
            }
            if ($accessibleHqIdsForFilter !== null && ! empty($accessibleHqIdsForFilter)) {
                $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                $dcrQuery->whereRaw('1 = 0');
            }
        }

        if ($fromDate) {
            $dcrQuery->whereDate('report_date', '>=', $fromDate);
        }
        if ($toDate) {
            $dcrQuery->whereDate('report_date', '<=', $toDate);
        }

        $reports = $dcrQuery->orderBy('report_date', 'desc')->get();

        // Apply HQ / Area / Region filters (collection filter)
        $headquarterQuery = PharmaHeadquarter::with('area')->where('company_id', company()->id);
        if ($this->viewPermission != 'all' && $accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
            $headquarterQuery->whereIn('id', $accessibleHqIdsForFilter);
        }
        $headquarters = $headquarterQuery->orderBy('name')->get();
        if ($selectedHQ) {
            $hq = $headquarters->firstWhere('id', $selectedHQ);
            if ($hq) {
                $reports = $reports->filter(fn ($r) => $r->headquarter === $hq->name);
            }
        }
        if ($selectedArea) {
            $reports = $reports->filter(function ($r) use ($selectedArea) {
                $areaId = optional(optional(optional($r->user)->employeeDetail)->headquarter)->area_id;
                return $areaId != null && (int) $areaId === (int) $selectedArea;
            });
        }
        if ($selectedRegion) {
            $reports = $reports->filter(function ($r) use ($selectedRegion) {
                $regionId = optional(optional(optional(optional($r->user)->employeeDetail)->headquarter)->area)->region_id;
                return $regionId != null && (int) $regionId === (int) $selectedRegion;
            });
        }

        // Group by user_id and compute metrics
        $grouped = $reports->groupBy('user_id');
        $rows = [];
        foreach ($grouped as $userId => $userReports) {
            $first = $userReports->first();
            $user = $first->user;
            $ed = $user ? $user->employeeDetail : null;
            $hqName = $ed && $ed->headquarter ? $ed->headquarter->name : '-';
            $areaName = $ed && $ed->headquarter && $ed->headquarter->area ? $ed->headquarter->area->name : '-';
            $workingDays = $userReports->pluck('report_date')->unique()->count();
            $doctorCalls = $userReports->sum('doctor_visits_count');
            $chemistCalls = $userReports->sum('chemist_visits_count');
            $stockistCalls = $userReports->sum('stockist_visits_count');
            $totalCalls = $doctorCalls + $chemistCalls + $stockistCalls;
            $callAverage = $workingDays > 0 ? round($totalCalls / $workingDays, 2) : 0;
            $rows[] = [
                'employee_name' => $user ? $user->name : '-',
                'employee_id' => $ed && $ed->employee_id ? $ed->employee_id : '-',
                'hq' => $hqName,
                'area' => $areaName,
                'working_days' => $workingDays,
                'doctor_calls' => $doctorCalls,
                'chemist_calls' => $chemistCalls,
                'stockist_calls' => $stockistCalls,
                'total_calls' => $totalCalls,
                'call_average' => $callAverage,
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['employee_name'], $b['employee_name']));

        $areaIds = $headquarters->pluck('area_id')->filter()->unique()->values();
        $areas = $areaIds->isNotEmpty()
            ? PharmaArea::whereIn('id', $areaIds)->where('company_id', company()->id)->orderBy('name')->get()
            : collect();
        $regionIds = $areas->pluck('region_id')->filter()->unique()->values();
        $regions = $regionIds->isNotEmpty()
            ? PharmaRegion::whereIn('id', $regionIds)->where('company_id', company()->id)->orderBy('name')->get()
            : collect();

        $this->pageTitle = 'DCR Reports';
        $this->employees = User::allEmployees(null, true);
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->selectedEmployeeId = $employeeId;
        $this->selectedHQ = $selectedHQ;
        $this->selectedArea = $selectedArea;
        $this->selectedRegion = $selectedRegion;
        $this->headquarters = $headquarters;
        $this->areas = $areas;
        $this->regions = $regions;
        $this->rows = $rows;

        return view('dcr-reports.call-average', $this->data);
    }

    /**
     * Area performance report: metrics by area (reports, visits, POB, distinct doctors/chemists/stockists).
     */
    public function areaPerformance(Request $request)
    {
        $this->viewPermission = user()->permission('view_dcr_reports');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $selectedHQ = $request->get('hq');

        $dcrQuery = DcrReport::with([
            'user.employeeDetail.headquarter.area',
        ])
            ->withCount(['doctorVisits', 'chemistVisits', 'stockistVisits'])
            ->where('company_id', company()->id);

        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();
        if ($this->viewPermission == 'all') {
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            if (!empty($viewableIds)) {
                $dcrQuery->whereIn('user_id', $viewableIds);
            }
            if (!user()->hasAdminLikeAccess() && $accessibleHqIdsForFilter !== null) {
                if (!empty($accessibleHqIdsForFilter)) {
                    $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                        $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                    });
                } else {
                    $dcrQuery->whereRaw('1 = 0');
                }
            }
        } else {
            $reportingEmployeeIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
            $dcrQuery->where(function ($q) use ($reportingEmployeeIds) {
                $q->where('user_id', user()->id);
                if (! empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
            if ($accessibleHqIdsForFilter !== null && ! empty($accessibleHqIdsForFilter)) {
                $dcrQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                $dcrQuery->whereRaw('1 = 0');
            }
        }

        if ($fromDate) {
            $dcrQuery->whereDate('report_date', '>=', $fromDate);
        }
        if ($toDate) {
            $dcrQuery->whereDate('report_date', '<=', $toDate);
        }

        $reports = $dcrQuery->orderBy('report_date', 'desc')->get();

        $headquarterQuery = PharmaHeadquarter::with('area')->where('company_id', company()->id);
        if ($this->viewPermission != 'all' && $accessibleHqIdsForFilter !== null && ! empty($accessibleHqIdsForFilter)) {
            $headquarterQuery->whereIn('id', $accessibleHqIdsForFilter);
        }
        $headquarters = $headquarterQuery->orderBy('name')->get();
        if ($selectedHQ) {
            $hq = $headquarters->firstWhere('id', $selectedHQ);
            if ($hq) {
                $reports = $reports->filter(fn ($r) => $r->headquarter === $hq->name);
            }
        }

        // Group by area (user's headquarter's area_id)
        $grouped = $reports->groupBy(function ($r) {
            $areaId = optional(optional(optional($r->user)->employeeDetail)->headquarter)->area_id;
            return $areaId ?? 'unknown';
        });

        $rows = [];
        foreach ($grouped as $areaKey => $areaReports) {
            if ($areaKey === 'unknown') {
                $areaName = 'No area';
            } else {
                $area = PharmaArea::where('id', $areaKey)->where('company_id', company()->id)->first();
                $areaName = $area ? $area->name : 'Area #' . $areaKey;
            }
            $reportIds = $areaReports->pluck('id')->toArray();
            $reportCount = $areaReports->count();
            $doctorCalls = $areaReports->sum('doctor_visits_count');
            $chemistCalls = $areaReports->sum('chemist_visits_count');
            $stockistCalls = $areaReports->sum('stockist_visits_count');
            $totalPob = $areaReports->sum(function ($r) {
                return (float) ($r->pob ?? 0) + (float) ($r->pob_doctor1 ?? 0) + (float) ($r->pob_doctor2 ?? 0) + (float) ($r->pob_doctor3 ?? 0)
                    + (float) ($r->chemist_pob_amount1 ?? 0) + (float) ($r->chemist_pob_amount2 ?? 0) + (float) ($r->chemist_pob_amount3 ?? 0) + (float) ($r->chemist_pob_amount4 ?? 0)
                    + (float) ($r->pob_stockist ?? 0) + (float) ($r->stockist_pob_amount ?? 0);
            });
            $distinctDoctors = $reportIds ? \Illuminate\Support\Facades\DB::table('dcr_doctor_visits')->whereIn('dcr_report_id', $reportIds)->selectRaw('count(distinct doctor_id) as c')->value('c') : 0;
            $distinctChemists = $reportIds ? \Illuminate\Support\Facades\DB::table('dcr_chemist_visits')->whereIn('dcr_report_id', $reportIds)->selectRaw('count(distinct chemist_id) as c')->value('c') : 0;
            $distinctStockists = $reportIds ? \Illuminate\Support\Facades\DB::table('dcr_stockist_visits')->whereIn('dcr_report_id', $reportIds)->selectRaw('count(distinct stockist_id) as c')->value('c') : 0;
            $rows[] = [
                'area_name' => $areaName,
                'report_count' => $reportCount,
                'doctor_calls' => $doctorCalls,
                'chemist_calls' => $chemistCalls,
                'stockist_calls' => $stockistCalls,
                'distinct_doctors' => $distinctDoctors,
                'distinct_chemists' => $distinctChemists,
                'distinct_stockists' => $distinctStockists,
                'total_pob' => round($totalPob, 2),
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['area_name'], $b['area_name']));

        $this->pageTitle = 'DCR Reports';
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->selectedHQ = $selectedHQ;
        $this->headquarters = $headquarters;
        $this->rows = $rows;

        return view('dcr-reports.area-performance', $this->data);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->buildDcrFormData(null);

        if (request()->ajax()) {
            $html = view('dcr-reports.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('dcr-reports.create', $this->data);
    }

    /**
     * Edit a header-only DCR (add visit lines). Same form as create; submits to update().
     */
    public function edit($id)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(! $this->userCanAddOrEditDcrReports());

        $dcr = DcrReport::with(['doctorVisits', 'chemistVisits', 'stockistVisits', 'user'])
            ->where('company_id', company()->id)
            ->findOrFail($id);

        abort_403(!$this->canViewDcr($dcr));
        abort_403($dcr->status === 'approved' || $dcr->approved);

        if ($dcr->doctorVisits->isNotEmpty() || $dcr->chemistVisits->isNotEmpty() || $dcr->stockistVisits->isNotEmpty()) {
            return redirect()
                ->route('dcr-management.index')
                ->with('error', __('This DCR already has visit lines. Only header-only reports can be opened here.'));
        }

        $this->buildDcrFormData($dcr);
        $this->pageTitle = __('app.edit') . ' DCR';

        return view('dcr-reports.create', $this->data);
    }

    /**
     * Shared form data for create / edit DCR (create uses $editing = null).
     */
    private function buildDcrFormData(?DcrReport $editing): void
    {
        // Get accessible headquarters for filtering
        $accessibleHqIds = $this->accessibleHeadquarterIds();
        $accessibleAreaIds = $this->accessibleAreaIds();

        // Doctors/chemists/stockists for visit pickers are loaded via AJAX (getStationCustomers) per selected station
        // to avoid embedding full company lists and to match HQ/station scope. Empty arrays keep the page light.
        $this->doctors = collect();
        $this->chemists = collect();
        $this->stockists = collect();

        // Load products from Worksuite purchase-products (active products only)
        $this->products = Product::where('company_id', company()->id)
            ->orderBy('name', 'asc')
            ->get();

        // Filter headquarters by accessible headquarters
        $headquartersQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id);

        if ($accessibleHqIds !== null && ! user()->hasAdminLikeAccess()) {
            if (! empty($accessibleHqIds)) {
                $headquartersQuery->whereIn('id', $accessibleHqIds);
                $this->headquarters = $headquartersQuery->get();
            } elseif ($accessibleAreaIds !== null && ! empty($accessibleAreaIds)) {
                $this->headquarters = $headquartersQuery->whereIn('area_id', $accessibleAreaIds)->get();
            } else {
                $this->headquarters = collect();
            }
        } else {
            // Admin: show all headquarters
            $this->headquarters = $headquartersQuery->get();
        }

        // Get designations (hierarchy names) for "Work With" field - same as tour plan
        $this->workedWithDesignations = [
            'Independent',
            'Medical Representative',
            'ABM',
            'RBM',
            'Sales Manager',
            'Zonal Manager',
            'PMT',
            'HO'
        ];

        if ($editing !== null) {
            $this->editingDcr = $editing;
            $this->canAddForOthers = false;
            $this->employeesForDropdown = collect();

            $hqFromName = $editing->headquarter
                ? PharmaHeadquarter::where('company_id', company()->id)->where('name', $editing->headquarter)->first()
                : null;
            if (! $hqFromName && $editing->headquarter) {
                $hqFromName = PharmaHeadquarter::where('company_id', company()->id)
                    ->where('name', 'like', '%' . $editing->headquarter . '%')
                    ->first();
            }
            if ($hqFromName && $this->headquarters->where('id', $hqFromName->id)->isEmpty()) {
                $hqFromName->load(['exstations', 'outstations', 'area']);
                $this->headquarters = $this->headquarters->push($hqFromName)->unique('id')->values();
            }

            $this->reportDate = $editing->report_date ? $editing->report_date->format('Y-m-d') : now()->format('Y-m-d');
            $this->userHeadquarter = $hqFromName ? $hqFromName->id : null;

            if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
                $this->userHeadquarter = $this->headquarters->first()->id;
            }

            $this->reportingManagerId = $editing->submitted_to;
            $this->managers = collect();
            if ($editing->submitted_to) {
                $mgr = User::with(['employeeDetail.designation'])
                    ->whereHas('employeeDetail')
                    ->where('company_id', company()->id)
                    ->find($editing->submitted_to);
                if ($mgr) {
                    $this->managers = collect([$mgr]);
                }
            }

            $this->loggedInEmployeeId = optional(user()->employeeDetail)->employee_id ?? optional(user()->employeeDetails)->employee_id;
            $this->draftPayload = null;
            $this->dcrDraftResumeInfo = $this->buildDcrDraftResumeInfoFromPayload(null);
        } else {
            $this->editingDcr = null;
            $this->draftPayload = null;
            // Admin / add_dcr_reports all: can add DCR on behalf of another employee
            $this->canAddForOthers = user()->hasAdminLikeAccess() || $this->addPermission === 'all';
            $this->employeesForDropdown = collect();
            if ($this->canAddForOthers) {
                if (user()->hasAdminLikeAccess()) {
                    $this->employeesForDropdown = User::with(['employeeDetail.designation', 'employeeDetail.headquarter', 'employeeDetails'])
                        ->whereHas('employeeDetail')
                        ->where('company_id', company()->id)
                        ->orderBy('name')
                        ->get()
                        ->map(function ($u) {
                            $empDetail = $u->employeeDetail ?? $u->employeeDetails;

                            return [
                                'id' => $u->id,
                                'name' => $u->name,
                                'designation' => optional(optional($empDetail)->designation)->name ?? '-',
                                'headquarter_id' => optional($empDetail)->headquarter_id ?? optional($empDetail)->pharma_headquarter_id,
                                'employee_id' => optional($empDetail)->employee_id,
                            ];
                        });
                } else {
                    $reportingIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
                    $submittedIds = DcrReport::where('submitted_to', user()->id)->where('company_id', company()->id)->distinct()->pluck('user_id')->toArray();
                    $ids = array_unique(array_merge($reportingIds, $submittedIds));
                    $ids = array_values(array_intersect($ids, RoleHierarchy::userIdsViewableBy(user(), company()->id)));
                    if (! empty($ids)) {
                        $this->employeesForDropdown = User::with(['employeeDetail.designation', 'employeeDetail.headquarter', 'employeeDetails'])
                            ->whereHas('employeeDetail')
                            ->whereIn('id', $ids)
                            ->where('company_id', company()->id)
                            ->orderBy('name')
                            ->get()
                            ->map(function ($u) {
                                $empDetail = $u->employeeDetail ?? $u->employeeDetails;

                                return [
                                    'id' => $u->id,
                                    'name' => $u->name,
                                    'designation' => optional(optional($empDetail)->designation)->name ?? '-',
                                    'headquarter_id' => optional($empDetail)->headquarter_id ?? optional($empDetail)->pharma_headquarter_id,
                                    'employee_id' => optional($empDetail)->employee_id,
                                ];
                            });
                    }
                }
            }

            $this->loggedInEmployeeId = optional(user()->employeeDetail)->employee_id ?? optional(user()->employeeDetails)->employee_id;

            // Draft DCR resume: latest draft for this user (not only "current calendar month" — a March draft
            // must still load in April or the form falls back to tour pre-fill and looks "reset").
            $draftDcr = $this->findLatestDraftDcrForUser(user()->id);

            if ($draftDcr) {
                $this->reportDate = $draftDcr->report_date ? $draftDcr->report_date->format('Y-m-d') : now()->format('Y-m-d');
                $this->draftPayload = $this->serializeDraftForView($draftDcr);

                $hqFromName = $draftDcr->headquarter
                    ? PharmaHeadquarter::where('company_id', company()->id)->where('name', $draftDcr->headquarter)->first()
                    : null;
                if (! $hqFromName && $draftDcr->headquarter) {
                    $hqFromName = PharmaHeadquarter::where('company_id', company()->id)
                        ->where('name', 'like', '%' . $draftDcr->headquarter . '%')
                        ->first();
                }
                if ($hqFromName && $this->headquarters->where('id', $hqFromName->id)->isEmpty()) {
                    $hqFromName->load(['exstations', 'outstations', 'area']);
                    $this->headquarters = $this->headquarters->push($hqFromName)->unique('id')->values();
                }
                $this->userHeadquarter = $hqFromName ? $hqFromName->id : null;

                if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
                    $this->userHeadquarter = $this->headquarters->first()->id;
                }

                $this->reportingManagerId = $draftDcr->submitted_to;
                if ($draftDcr->submitted_to) {
                    $this->managers = User::with(['employeeDetail.designation'])
                        ->whereHas('employeeDetail')
                        ->where('company_id', company()->id)
                        ->where('id', $draftDcr->submitted_to)
                        ->get();
                } else {
                    $this->managers = collect();
                }
            } else {
                // Get employee's reporting manager from HR (for logged-in user by default)
                $this->reportingManagerId = optional(user()->employeeDetails)->reporting_to;

                if ($this->reportingManagerId) {
                    $this->managers = User::with(['employeeDetail.designation'])
                        ->whereHas('employeeDetail')
                        ->where('id', $this->reportingManagerId)
                        ->where('company_id', company()->id)
                        ->get();
                } else {
                    $this->managers = collect();
                }

                $this->reportDate = $this->findLastPendingDate();

                $emp = user()->employeeDetails ?? user()->employeeDetail;
                $this->userHeadquarter = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;

                if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
                    $this->userHeadquarter = $this->headquarters->first()->id;
                }
            }
        }

        if ($editing === null) {
            $this->dcrDraftResumeInfo = $this->buildDcrDraftResumeInfoFromPayload($this->draftPayload ?? null);
        }

        $this->showHqDropdownForPharmaRoles = $this->headquarters->count() > 1;
    }
    
    /**
     * @param int|null $userId If null, uses logged-in user.
     * @return string Y-m-d
     */
    private function findLastPendingDate($userId = null)
    {
        $userId = $userId ?? user()->id;
        
        // Get the start of current month
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $today = now()->format('Y-m-d');
        
        // Get all approved tour dates for the user (from start of month to today)
        $approvedTours = \App\Models\Tour::where('user_id', $userId)
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('approved', 1);
            })
            ->where('date', '>=', $startOfMonth)
            ->where('date', '<=', $today)
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Dates with a non-draft DCR (drafts do not consume a "pending tour" slot)
        $submittedDates = DcrReport::where('user_id', $userId)
            ->where('report_date', '>=', $startOfMonth)
            ->where('report_date', '<=', $today)
            ->where('status', '!=', 'draft')
            ->pluck('report_date')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Find pending dates (approved tours without DCR)
        $pendingDates = array_diff($approvedTours, $submittedDates);
        
        // Return the FIRST (oldest) pending date chronologically, or today's date if none found
        if (!empty($pendingDates)) {
            // Sort ascending to get the oldest/first pending date
            sort($pendingDates);
            return $pendingDates[0];
        }
        
        // If no pending dates, return today's date
        return $today;
    }
    
    public function getTourByDate(Request $request)
    {
        $date = $request->date;
        $userId = $request->user_id ?? user()->id;
        
        // Log for debugging
        \Log::info('getTourByDate called', [
            'date' => $date,
            'userId' => $userId
        ]);
        
        // Try to find tour with status OR approved field
        $tour = \App\Models\Tour::with([
            'headquarter.exstations',
            'headquarter.outstations',
            'headquarter.area',
            'submittedTo',
            'approvedBy',
        ])
            ->where('user_id', $userId)
            ->where('date', $date)
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('approved', 1);
            })
            ->first();
        
        // Log what we found
        \Log::info('Tour search result', [
            'found' => $tour ? true : false,
            'tour_id' => $tour ? $tour->id : null,
            'tour_status' => $tour ? $tour->status : null,
            'tour_approved' => $tour ? $tour->approved : null
        ]);
        
        if ($tour) {
            $dateFormat = companyOrGlobalSetting()->date_format;
            $timeFormat = companyOrGlobalSetting()->time_format;
            
            return Reply::dataOnly([
                'status' => 'success',
                'tour' => [
                    'id' => $tour->id,
                    'date' => $tour->date->translatedFormat($dateFormat),
                    'day' => $tour->day,
                    'headquarter' => $tour->headquarter->name ?? '-',
                    'headquarter_id' => $tour->headquarter_id ?? $tour->pharma_headquarter_id ?? null,
                    'headquarter_bundle' => $this->headquarterToDcrPickerArray($tour->headquarter),
                    'station' => $tour->station,
                    'work_status' => $tour->work_status,
                    'work_with' => $tour->work_with,
                    'remark' => $tour->remark,
                    'approved_by' => $tour->approvedBy->name ?? '-',
                    'approved_at' => $tour->approved_at ? $tour->approved_at->translatedFormat($dateFormat . ' ' . $timeFormat) : '-',
                ]
            ]);
        }
        
        return Reply::dataOnly([
            'status' => 'error',
            'message' => 'No approved tour found for this date'
        ]);
    }

    /**
     * AJAX: Get DCR context (report date, reporting manager, HQ) for a given employee.
     * Used when admin/add-for-others user selects an employee so form can reflect that employee's data.
     */
    public function getDcrContextForEmployee(Request $request)
    {
        $addPermission = user()->permission('add_dcr_reports');
        abort_403(!in_array($addPermission, ['all', 'added']));
        $canAddForOthers = user()->hasAdminLikeAccess() || $addPermission === 'all';
        abort_403(!$canAddForOthers);

        $userId = (int) $request->user_id;
        if (!$userId) {
            return Reply::dataOnly(['status' => 'error', 'message' => 'Invalid user']);
        }

        $allowedIds = $this->getAllowedEmployeeIdsForAddDcr();
        if (!in_array($userId, $allowedIds)) {
            return Reply::dataOnly(['status' => 'error', 'message' => 'Not allowed to add DCR for this employee']);
        }

        $emp = User::with(['employeeDetail'])->where('id', $userId)->where('company_id', company()->id)->first();
        if (!$emp || !$emp->employeeDetail) {
            return Reply::dataOnly(['status' => 'error', 'message' => 'Employee not found']);
        }

        $reportDate = $this->findLastPendingDate($userId);
        $reportingTo = optional($emp->employeeDetail)->reporting_to;
        $userHeadquarter = $emp->employeeDetail->headquarter_id ?? $emp->employeeDetail->pharma_headquarter_id ?? null;

        $managers = collect();
        if ($reportingTo) {
            $managers = User::with(['employeeDetail.designation'])
                ->whereHas('employeeDetail')
                ->where('id', $reportingTo)
                ->where('company_id', company()->id)
                ->get()
                ->map(function ($u) {
                    return ['id' => $u->id, 'name' => $u->name, 'designation' => optional($u->employeeDetail)->designation->name ?? null];
                });
        }

        return Reply::dataOnly([
            'status' => 'success',
            'report_date' => $reportDate,
            'reporting_manager_id' => $reportingTo,
            'managers' => $managers->values()->toArray(),
            'user_headquarter' => $userHeadquarter,
        ]);
    }

    /**
     * IDs of employees for whom the current user is allowed to add DCR (admin or add_dcr_reports all).
     */
    private function getAllowedEmployeeIdsForAddDcr(): array
    {
        if (user()->hasAdminLikeAccess()) {
            return User::whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->pluck('id')
                ->toArray();
        }
        $reportingIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
        $submittedIds = DcrReport::where('submitted_to', user()->id)->where('company_id', company()->id)->distinct()->pluck('user_id')->toArray();
        $ids = array_values(array_unique(array_merge($reportingIds, $submittedIds)));

        return array_values(array_intersect($ids, RoleHierarchy::userIdsViewableBy(user(), company()->id)));
    }

    /**
     * Approved tour headquarter for this user on this report date (field visit territory).
     */
    private function approvedTourHeadquarterIdForUserOnDate(int $userId, string $dateYmd): ?int
    {
        $tour = Tour::where('company_id', company()->id)
            ->where('user_id', $userId)
            ->whereDate('date', $dateYmd)
            ->where(function ($q) {
                $q->where('status', 'approved')->orWhere('approved', 1);
            })
            ->first();

        if (! $tour || ! $tour->headquarter_id) {
            return null;
        }

        return (int) $tour->headquarter_id;
    }

    /**
     * Whether DCR header headquarter is allowed: normal geography OR same as approved tour for that date.
     */
    private function headquarterAllowedForDcrContext(User $dcrOwner, $reportDate, int $headquarterId): bool
    {
        $allowedHqIds = $this->accessibleHeadquarterIds($dcrOwner);
        if ($allowedHqIds === null) {
            return true;
        }
        $hqId = (int) $headquarterId;
        foreach ($allowedHqIds as $id) {
            if ((int) $id === $hqId) {
                return true;
            }
        }
        try {
            $dateYmd = Carbon::parse($reportDate)->toDateString();
        } catch (\Throwable $e) {
            return false;
        }
        $tourHq = $this->approvedTourHeadquarterIdForUserOnDate((int) $dcrOwner->id, $dateYmd);

        return $tourHq !== null && $tourHq === $hqId;
    }

    private function headquarterToDcrPickerArray(?PharmaHeadquarter $hq): ?array
    {
        if (! $hq) {
            return null;
        }
        $hq->loadMissing(['exstations', 'outstations', 'area']);

        return [
            'id' => $hq->id,
            'name' => $hq->name,
            'area' => $hq->area ? ['id' => $hq->area->id, 'name' => $hq->area->name] : null,
            'exstations' => $hq->exstations->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values()->all(),
            'outstations' => $hq->outstations->map(fn ($x) => ['id' => $x->id, 'name' => $x->name])->values()->all(),
        ];
    }

    private function stationRequestMatchesTourTerritory(Tour $tour, string $stationType, $stationId): bool
    {
        $stationId = (int) $stationId;
        $tourHqId = (int) $tour->headquarter_id;
        $st = strtolower($stationType);
        if (in_array($st, ['hq', 'headquarter'], true)) {
            return $stationId === $tourHqId;
        }
        if ($st === 'exstation') {
            return PharmaHeadquarterAssign::where('company_id', company()->id)
                ->where('headquarter_id', $tourHqId)
                ->where('station', 'exstation')
                ->where('station_id', $stationId)
                ->exists();
        }
        if ($st === 'outstation') {
            return PharmaHeadquarterAssign::where('company_id', company()->id)
                ->where('headquarter_id', $tourHqId)
                ->where('station', 'outstation')
                ->where('station_id', $stationId)
                ->exists();
        }

        return false;
    }

    /**
     * Merge tour day's headquarter into AJAX customer scope when the requested station belongs to that tour.
     *
     * @param  array<int, mixed>  $accessibleHqIds
     * @param  array<int, mixed>  $accessibleAreaIds
     * @return array{0: array, 1: array}
     */
    private function expandStationCustomerAccessibleScopeForTour(
        Request $request,
        string $stationType,
        $stationId,
        array $accessibleHqIds,
        array $accessibleAreaIds
    ): array {
        $reportDate = $request->get('report_date');
        if ($reportDate === null || $reportDate === '') {
            return [$accessibleHqIds, $accessibleAreaIds];
        }
        try {
            $dateYmd = Carbon::parse($reportDate)->toDateString();
        } catch (\Throwable $e) {
            return [$accessibleHqIds, $accessibleAreaIds];
        }
        $dcrUserId = (int) user()->id;
        $reqUid = (int) $request->get('user_id', 0);
        if ($reqUid > 0 && $reqUid !== $dcrUserId) {
            if (in_array($reqUid, $this->getAllowedEmployeeIdsForAddDcr(), true)) {
                $dcrUserId = $reqUid;
            }
        }
        $tour = Tour::where('company_id', company()->id)
            ->where('user_id', $dcrUserId)
            ->whereDate('date', $dateYmd)
            ->where(function ($q) {
                $q->where('status', 'approved')->orWhere('approved', 1);
            })
            ->first();
        if (! $tour || ! $tour->headquarter_id) {
            return [$accessibleHqIds, $accessibleAreaIds];
        }
        if (! $this->stationRequestMatchesTourTerritory($tour, $stationType, $stationId)) {
            return [$accessibleHqIds, $accessibleAreaIds];
        }
        $tourHqId = (int) $tour->headquarter_id;
        $intHqs = array_map('intval', $accessibleHqIds);
        if (! in_array($tourHqId, $intHqs, true)) {
            $accessibleHqIds = array_values(array_unique(array_merge($accessibleHqIds, [$tourHqId])));
        }

        return [$accessibleHqIds, $accessibleAreaIds];
    }

    private function userCanAddDcrReports(): bool
    {
        return in_array(user()->permission('add_dcr_reports'), ['all', 'added'], true);
    }

    /**
     * edit_dcr_reports uses ALL_ADDED_NONE; add_dcr_reports uses ALL_NONE (typically only all/none).
     * Header-only edit must allow users who can edit but not add new DCRs.
     */
    private function userCanEditDcrReportsPermission(): bool
    {
        return in_array(user()->permission('edit_dcr_reports'), ['all', 'added'], true);
    }

    private function userCanAddOrEditDcrReports(): bool
    {
        if ($this->userCanAddDcrReports() || $this->userCanEditDcrReportsPermission()) {
            return true;
        }
        $approvePerm = user()->permission('approve_dcr_reports');
        if (is_string($approvePerm) && in_array($approvePerm, ['all', 'added', 'owned', 'both'], true)) {
            return true;
        }

        return false;
    }

    /**
     * SRS 3.2.6: Whether the current user can view this DCR (hierarchy + HQ/Area/Region scope).
     * Used to enforce visibility on destroy, approve, reject.
     */
    private function canViewDcr(DcrReport $dcr): bool
    {
        if ((int) $dcr->company_id !== (int) company()->id) {
            return false;
        }

        // Own DCR — always visible (HQ can be missing on employee record; index still lists own rows)
        if ((int) $dcr->user_id === (int) user()->id) {
            return true;
        }

        // Submitted to current user (manager) — must be able to open/edit header-only rows routed to them
        if ($dcr->submitted_to && (int) $dcr->submitted_to === (int) user()->id) {
            return true;
        }

        $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
        if (!in_array($dcr->user_id, $viewableIds, true)) {
            return false;
        }
        if (user()->hasAdminLikeAccess()) {
            return true;
        }
        $hqIds = $this->accessibleHeadquarterIds();
        if ($hqIds === null) {
            return true;
        }
        $creator = $dcr->relationLoaded('user') ? $dcr->user : $dcr->load('user')->user;
        if (!$creator) {
            return false;
        }
        $emp = $creator->employeeDetail ?? $creator->employeeDetails ?? null;
        if (!$emp) {
            return false;
        }
        $creatorHqId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
        if ($creatorHqId === null) {
            return false;
        }
        return in_array((int) $creatorHqId, array_map('intval', $hqIds), true);
    }

    /**
     * SRS 3.2.5: Auto-save a single doctor/chemist/stockist call with geo-tag.
     * Validates GPS and 100m rule, gets or creates draft DCR, attaches the visit.
     */
    public function storeVisit(Request $request)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(! $this->userCanAddOrEditDcrReports());

        $request->validate([
            'report_date' => 'required|date',
            'work_status' => 'required|string',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'required|exists:users,id',
            'visit_type' => 'required|in:doctor,chemist,stockist',
            'visit_id' => 'nullable|integer',
        ]);

        $dcrOwnerId = user()->id;
        $canAddForOthers = user()->hasAdminLikeAccess() || $this->addPermission === 'all';
        if ($canAddForOthers && $request->filled('user_id')) {
            $requestedId = (int) $request->user_id;
            $allowedIds = $this->getAllowedEmployeeIdsForAddDcr();
            if (in_array($requestedId, $allowedIds)) {
                $dcrOwnerId = $requestedId;
            }
        }

        // Validate headquarter is in DCR owner's accessible list
        $dcrOwner = User::find($dcrOwnerId);
        $employeeToValidate = $dcrOwner ?: user();
        if (! $this->headquarterAllowedForDcrContext($employeeToValidate, $request->report_date, (int) $request->headquarter_id)) {
            return Reply::error(__('You can only create DCR for allocated headquarter(s).'));
        }

        $fieldWorkTypes = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
        if (!in_array($request->work_status, $fieldWorkTypes, true)) {
            return Reply::error(__('Auto-save is only for field work types. Use Close Day for other work types.'));
        }

        $maxMeters = config('dcr.max_distance_meters', 100);
        $enforceGps = config('dcr.enforce_gps_100m', true);
        $dcr = $this->getOrCreateDraft($request, $dcrOwnerId);

        if ($request->visit_type === 'doctor') {
            $doctorData = $request->input('doctor', []);
            if (empty($doctorData['doctor_id']) && empty($doctorData['speciality'])) {
                return Reply::error(__('Doctor or speciality is required.'));
            }
            $empLat = isset($doctorData['latitude']) ? (float) $doctorData['latitude'] : null;
            $empLon = isset($doctorData['longitude']) ? (float) $doctorData['longitude'] : null;
            $doctorId = $doctorData['doctor_id'] ?? null;
            if ($doctorId) {
                $doctor = Doctor::find($doctorId);
                if ($doctor && $doctor->latitude !== null && $doctor->longitude !== null) {
                    if ($enforceGps && ($empLat === null || $empLon === null)) {
                        return Reply::error(__('GPS location is required for this doctor call. Please capture your location.'));
                    }
                    if ($empLat !== null && $empLon !== null) {
                        $dist = Common::distanceInMeters($doctor->latitude, $doctor->longitude, $empLat, $empLon);
                        if ($enforceGps && $dist > $maxMeters) {
                            return Reply::error(__('Call allowed only within :meters m of the doctor. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                        }
                    }
                }
            }
            $doctorPayload = [
                'dcr_report_id' => $dcr->id,
                'doctor_id' => $doctorId,
                'speciality' => $doctorData['speciality'] ?? null,
                'area' => $doctorData['area'] ?? null,
                'msl' => $doctorData['msl'] ?? 0,
                'product1' => $doctorData['product1'] ?? null,
                'samples_unit1' => $doctorData['samples_unit1'] ?? 0,
                'pob1' => $doctorData['pob1'] ?? 0,
                'remark1' => $doctorData['remark1'] ?? null,
                'product2' => $doctorData['product2'] ?? null,
                'samples_unit2' => $doctorData['samples_unit2'] ?? 0,
                'pob2' => $doctorData['pob2'] ?? 0,
                'remark2' => $doctorData['remark2'] ?? null,
                'product3' => $doctorData['product3'] ?? null,
                'samples_unit3' => $doctorData['samples_unit3'] ?? 0,
                'pob3' => $doctorData['pob3'] ?? 0,
                'remark3' => $doctorData['remark3'] ?? null,
                'general_remark' => $doctorData['general_remark'] ?? null,
                'latitude' => $empLat,
                'longitude' => $empLon,
            ];
            $visitId = $request->input('visit_id');
            if ($visitId) {
                $visit = DcrDoctorVisit::where('id', $visitId)->where('dcr_report_id', $dcr->id)->first();
                if (! $visit) {
                    return Reply::error(__('Visit not found or does not belong to this draft DCR.'));
                }
                $visit->update($doctorPayload);
            } else {
                $visit = DcrDoctorVisit::create($doctorPayload);
            }
            return Reply::successWithData(__('Call saved.'), ['visit_id' => $visit->id, 'dcr_id' => $dcr->id]);
        }

        if ($request->visit_type === 'chemist') {
            $chemistData = $request->input('chemist', []);
            if (empty($chemistData['chemist_id']) && empty($chemistData['area'])) {
                return Reply::error(__('Chemist or area is required.'));
            }
            $empLat = isset($chemistData['latitude']) ? (float) $chemistData['latitude'] : null;
            $empLon = isset($chemistData['longitude']) ? (float) $chemistData['longitude'] : null;
            $chemistId = $chemistData['chemist_id'] ?? null;
            if ($chemistId) {
                $chemist = Chemist::find($chemistId);
                if ($chemist && $chemist->latitude !== null && $chemist->longitude !== null) {
                    if ($enforceGps && ($empLat === null || $empLon === null)) {
                        return Reply::error(__('GPS location is required for this chemist call. Please capture your location.'));
                    }
                    if ($empLat !== null && $empLon !== null) {
                        $dist = Common::distanceInMeters($chemist->latitude, $chemist->longitude, $empLat, $empLon);
                        if ($enforceGps && $dist > $maxMeters) {
                            return Reply::error(__('Call allowed only within :meters m of the chemist. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                        }
                    }
                }
            }
            $chemistPayload = [
                'dcr_report_id' => $dcr->id,
                'chemist_id' => $chemistId,
                'area' => $chemistData['area'] ?? null,
                'station' => $chemistData['station'] ?? null,
                'msl' => $chemistData['msl'] ?? 0,
                'rcpa1' => $chemistData['rcpa1'] ?? null,
                'pob_amount1' => $chemistData['pob_amount1'] ?? 0,
                'remark1' => $chemistData['remark1'] ?? null,
                'rcpa2' => $chemistData['rcpa2'] ?? null,
                'pob_amount2' => $chemistData['pob_amount2'] ?? 0,
                'remark2' => $chemistData['remark2'] ?? null,
                'rcpa3' => $chemistData['rcpa3'] ?? null,
                'pob_amount3' => $chemistData['pob_amount3'] ?? 0,
                'remark3' => $chemistData['remark3'] ?? null,
                'rcpa4' => $chemistData['rcpa4'] ?? null,
                'pob_amount4' => $chemistData['pob_amount4'] ?? 0,
                'remark4' => $chemistData['remark4'] ?? null,
                'general_remark' => $chemistData['general_remark'] ?? null,
                'latitude' => $empLat,
                'longitude' => $empLon,
            ];
            $visitId = $request->input('visit_id');
            if ($visitId) {
                $visit = DcrChemistVisit::where('id', $visitId)->where('dcr_report_id', $dcr->id)->first();
                if (! $visit) {
                    return Reply::error(__('Visit not found or does not belong to this draft DCR.'));
                }
                $visit->update($chemistPayload);
            } else {
                $visit = DcrChemistVisit::create($chemistPayload);
            }
            return Reply::successWithData(__('Call saved.'), ['visit_id' => $visit->id, 'dcr_id' => $dcr->id]);
        }

        if ($request->visit_type === 'stockist') {
            $stockistData = $request->input('stockist', []);
            if (empty($stockistData['stockist_id']) && empty($stockistData['area'])) {
                return Reply::error(__('Stockist or area is required.'));
            }
            $empLat = isset($stockistData['latitude']) ? (float) $stockistData['latitude'] : null;
            $empLon = isset($stockistData['longitude']) ? (float) $stockistData['longitude'] : null;
            $stockistId = $stockistData['stockist_id'] ?? null;
            if ($stockistId) {
                $stockist = Stockist::find($stockistId);
                if ($stockist && $stockist->latitude !== null && $stockist->longitude !== null) {
                    if ($enforceGps && ($empLat === null || $empLon === null)) {
                        return Reply::error(__('GPS location is required for this stockist call. Please capture your location.'));
                    }
                    if ($empLat !== null && $empLon !== null) {
                        $dist = Common::distanceInMeters($stockist->latitude, $stockist->longitude, $empLat, $empLon);
                        if ($enforceGps && $dist > $maxMeters) {
                            return Reply::error(__('Call allowed only within :meters m of the stockist. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                        }
                    }
                }
            }
            $stockistPayload = [
                'dcr_report_id' => $dcr->id,
                'stockist_id' => $stockistId,
                'area' => $stockistData['area'] ?? null,
                'station' => $stockistData['station'] ?? null,
                'msl' => $stockistData['msl'] ?? 0,
                'pob' => $stockistData['pob'] ?? null,
                'pob_amount' => $stockistData['pob_amount'] ?? 0,
                'proprietor' => $stockistData['proprietor'] ?? null,
                'proprietor_mobile' => $stockistData['proprietor_mobile'] ?? null,
                'general_remark' => $stockistData['general_remark'] ?? null,
                'latitude' => $empLat,
                'longitude' => $empLon,
            ];
            $visitId = $request->input('visit_id');
            if ($visitId) {
                $visit = DcrStockistVisit::where('id', $visitId)->where('dcr_report_id', $dcr->id)->first();
                if (! $visit) {
                    return Reply::error(__('Visit not found or does not belong to this draft DCR.'));
                }
                $visit->update($stockistPayload);
            } else {
                $visit = DcrStockistVisit::create($stockistPayload);
            }
            return Reply::successWithData(__('Call saved.'), ['visit_id' => $visit->id, 'dcr_id' => $dcr->id]);
        }

        return Reply::error(__('Invalid visit type.'));
    }

    /**
     * Delete a single draft DCR visit row (doctor/chemist/stockist).
     */
    public function destroyVisit(Request $request)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(! $this->userCanAddOrEditDcrReports());

        $request->validate([
            'visit_type' => 'required|in:doctor,chemist,stockist',
            'visit_id' => 'required|integer',
            'dcr_id' => 'required|integer|exists:dcr_reports,id',
        ]);

        $dcr = DcrReport::where('company_id', company()->id)->findOrFail($request->dcr_id);

        if ($dcr->status !== 'draft') {
            return Reply::error(__('Only draft DCR visits can be deleted from here.'));
        }

        $dcrOwnerId = user()->id;
        $canAddForOthers = user()->hasAdminLikeAccess() || $this->addPermission === 'all';
        if ($canAddForOthers && $request->filled('user_id')) {
            $requestedId = (int) $request->user_id;
            $allowedIds = $this->getAllowedEmployeeIdsForAddDcr();
            if (in_array($requestedId, $allowedIds, true)) {
                $dcrOwnerId = $requestedId;
            }
        }

        if ((int) $dcr->user_id !== (int) $dcrOwnerId) {
            return Reply::error(__('You cannot delete this visit.'));
        }

        $deleted = false;
        if ($request->visit_type === 'doctor') {
            $deleted = DcrDoctorVisit::where('id', $request->visit_id)->where('dcr_report_id', $dcr->id)->delete() > 0;
        } elseif ($request->visit_type === 'chemist') {
            $deleted = DcrChemistVisit::where('id', $request->visit_id)->where('dcr_report_id', $dcr->id)->delete() > 0;
        } else {
            $deleted = DcrStockistVisit::where('id', $request->visit_id)->where('dcr_report_id', $dcr->id)->delete() > 0;
        }

        if (! $deleted) {
            return Reply::error(__('Visit not found.'));
        }

        return Reply::success(__('Visit removed.'));
    }

    /**
     * Resolve DCR header fields from a storeVisit / Close Day style request.
     */
    private function resolveDraftHeaderFromRequest(Request $request): array
    {
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        $headquarterName = $headquarter ? $headquarter->name : null;
        if ($request->station) {
            $hqByName = PharmaHeadquarter::where('name', $request->station)->first();
            if ($hqByName) {
                $headquarterName = $hqByName->name;
            }
        }
        $workWithString = is_array($request->work_with) ? implode(',', $request->work_with) : (string) $request->work_with;

        return [
            'headquarter' => $headquarterName,
            'station' => $request->station ?? '',
            'work_status' => $request->work_status,
            'work_with' => $workWithString,
            'remark' => $request->remark ?? '',
            'submitted_to' => $request->submitted_to,
        ];
    }

    /**
     * Normalize work_status for comparing tour vs DCR (first segment if comma-separated).
     */
    private function normalizeWorkStatusForCompare(?string $ws): string
    {
        if ($ws === null || $ws === '') {
            return '';
        }
        $parts = explode(',', $ws);

        return trim($parts[0]);
    }

    /**
     * When draft header work type differs from approved tour for same date, update tour (if allowed) and log.
     */
    private function syncTourWithDraftIfNeeded(DcrReport $draft): void
    {
        if (! $draft->report_date) {
            return;
        }
        $tour = Tour::where('company_id', company()->id)
            ->where('user_id', $draft->user_id)
            ->whereDate('date', $draft->report_date->format('Y-m-d'))
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhere('approved', 1);
            })
            ->first();
        if (! $tour) {
            return;
        }
        $oldWs = $this->normalizeWorkStatusForCompare($tour->work_status);
        $newWs = $this->normalizeWorkStatusForCompare($draft->work_status);
        if ($oldWs === $newWs) {
            return;
        }

        $reportDate = $draft->report_date instanceof Carbon ? $draft->report_date : Carbon::parse($draft->report_date);
        $monthLocked = TourMonthLock::isLocked((int) company()->id, (int) $reportDate->year, (int) $reportDate->month);
        $tourLockedAfterApproval = $tour->approved && ! user()->hasAdminLikeAccess();

        if ($tourLockedAfterApproval || ($monthLocked && ! user()->hasAdminLikeAccess())) {
            DcrTourSyncLog::create([
                'company_id' => company()->id,
                'user_id' => $draft->user_id,
                'report_date' => $reportDate->format('Y-m-d'),
                'dcr_report_id' => $draft->id,
                'tour_id' => $tour->id,
                'action' => 'tour_work_status_sync_skipped',
                'old_value' => $tour->work_status,
                'new_value' => $draft->work_status,
                'meta' => [
                    'reason' => $tourLockedAfterApproval ? 'tour_approved_non_admin' : 'month_locked',
                ],
                'created_by' => user()->id,
            ]);

            return;
        }

        $previousTourWs = $tour->work_status;
        $tour->update(['work_status' => $draft->work_status]);

        DcrTourSyncLog::create([
            'company_id' => company()->id,
            'user_id' => $draft->user_id,
            'report_date' => $reportDate->format('Y-m-d'),
            'dcr_report_id' => $draft->id,
            'tour_id' => $tour->id,
            'action' => 'tour_work_status_synced',
            'old_value' => $previousTourWs,
            'new_value' => $draft->work_status,
            'meta' => ['source' => 'dcr_draft'],
            'created_by' => user()->id,
        ]);
    }

    /**
     * Get or create a draft DCR for the given user and report date (SRS 3.2.5 auto-save).
     * Existing draft rows are merged from the request so work type / station stay in sync with the form.
     */
    private function getOrCreateDraft(Request $request, int $dcrOwnerId): DcrReport
    {
        $header = $this->resolveDraftHeaderFromRequest($request);
        $draft = DcrReport::where('user_id', $dcrOwnerId)
            ->whereDate('report_date', $request->report_date)
            ->where('status', 'draft')
            ->first();
        if ($draft) {
            $draft->update([
                'submitted_to' => $header['submitted_to'],
                'headquarter' => $header['headquarter'],
                'station' => $header['station'],
                'work_status' => $header['work_status'],
                'work_with' => $header['work_with'],
                'remark' => $header['remark'],
            ]);
            $draft->refresh();
            $this->syncTourWithDraftIfNeeded($draft);

            return $draft;
        }
        $created = DcrReport::create([
            'company_id' => company()->id,
            'user_id' => $dcrOwnerId,
            'submitted_to' => $header['submitted_to'],
            'approved' => false,
            'status' => 'draft',
            'report_date' => $request->report_date,
            'headquarter' => $header['headquarter'],
            'station' => $header['station'],
            'work_status' => $header['work_status'],
            'work_with' => $header['work_with'],
            'remark' => $header['remark'],
        ]);
        $this->syncTourWithDraftIfNeeded($created);

        return $created;
    }

    /**
     * Serialize draft DCR + visits for create.blade.php hydration (JSON-safe).
     */
    private function serializeDraftForView(DcrReport $draft): array
    {
        $hqId = $this->resolveHeadquarterIdForDraft($draft);
        $workWith = [];
        if ($draft->work_with) {
            $workWith = array_values(array_filter(array_map('trim', explode(',', $draft->work_with))));
        }

        return [
            'dcr_id' => $draft->id,
            'report_date' => $draft->report_date ? $draft->report_date->format('Y-m-d') : null,
            'headquarter_id' => $hqId,
            'headquarter_name' => $draft->headquarter,
            'work_status' => $draft->work_status,
            'station' => $draft->station,
            'work_with' => $workWith,
            'submitted_to' => $draft->submitted_to,
            'remark' => $draft->remark ?? '',
            'doctor_visits' => $draft->doctorVisits->map(function ($v) {
                return [
                    'id' => $v->id,
                    'doctor_id' => $v->doctor_id,
                    'speciality' => $v->speciality,
                    'area' => $v->area,
                    'msl' => $v->msl,
                    'product1' => $v->product1,
                    'samples_unit1' => $v->samples_unit1,
                    'pob1' => $v->pob1,
                    'remark1' => $v->remark1,
                    'product2' => $v->product2,
                    'samples_unit2' => $v->samples_unit2,
                    'pob2' => $v->pob2,
                    'remark2' => $v->remark2,
                    'product3' => $v->product3,
                    'samples_unit3' => $v->samples_unit3,
                    'pob3' => $v->pob3,
                    'remark3' => $v->remark3,
                    'general_remark' => $v->general_remark,
                    'latitude' => $v->latitude,
                    'longitude' => $v->longitude,
                    'doctor_label' => $v->doctor_id ? (optional($v->doctor)->fullname ?? $v->doctor_name ?? ('Doctor #'.$v->doctor_id)) : '',
                ];
            })->values()->all(),
            'chemist_visits' => $draft->chemistVisits->map(function ($v) {
                return [
                    'id' => $v->id,
                    'chemist_id' => $v->chemist_id,
                    'area' => $v->area,
                    'station' => $v->station,
                    'msl' => $v->msl,
                    'rcpa1' => $v->rcpa1,
                    'pob_amount1' => $v->pob_amount1,
                    'remark1' => $v->remark1,
                    'rcpa2' => $v->rcpa2,
                    'pob_amount2' => $v->pob_amount2,
                    'remark2' => $v->remark2,
                    'rcpa3' => $v->rcpa3,
                    'pob_amount3' => $v->pob_amount3,
                    'remark3' => $v->remark3,
                    'rcpa4' => $v->rcpa4,
                    'pob_amount4' => $v->pob_amount4,
                    'remark4' => $v->remark4,
                    'general_remark' => $v->general_remark,
                    'latitude' => $v->latitude,
                    'longitude' => $v->longitude,
                    'chemist_label' => $v->chemist_id ? (optional($v->chemist)->shopname ?? $v->chemist_name ?? ('Chemist #'.$v->chemist_id)) : '',
                ];
            })->values()->all(),
            'stockist_visits' => $draft->stockistVisits->map(function ($v) {
                return [
                    'id' => $v->id,
                    'stockist_id' => $v->stockist_id,
                    'area' => $v->area,
                    'station' => $v->station,
                    'msl' => $v->msl,
                    'pob' => $v->pob,
                    'pob_amount' => $v->pob_amount,
                    'proprietor' => $v->proprietor,
                    'proprietor_mobile' => $v->proprietor_mobile,
                    'general_remark' => $v->general_remark,
                    'latitude' => $v->latitude,
                    'longitude' => $v->longitude,
                    'stockist_label' => $v->stockist_id ? (optional($v->stockist)->shopname ?? $v->stockist_name ?? ('Stockist #'.$v->stockist_id)) : '',
                ];
            })->values()->all(),
        ];
    }

    /**
     * Latest draft DCR row for resume (same rules as create form).
     */
    private function findLatestDraftDcrForUser(int $userId): ?DcrReport
    {
        $draftFrom = now()->copy()->subMonths(6)->startOfMonth()->format('Y-m-d');
        // Do not cap at "today": future-dated drafts must still resume after refresh.

        return DcrReport::with(['doctorVisits.doctor', 'chemistVisits.chemist', 'stockistVisits.stockist'])
            ->where('company_id', company()->id)
            ->where('user_id', $userId)
            ->where('status', 'draft')
            ->whereDate('report_date', '>=', $draftFrom)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Resolve pharma headquarter id for draft hydration (name/station quirks after refresh).
     */
    private function resolveHeadquarterIdForDraft(DcrReport $draft): ?int
    {
        $companyId = company()->id;

        $tryName = function (?string $raw) use ($companyId): ?PharmaHeadquarter {
            if ($raw === null || trim($raw) === '') {
                return null;
            }
            $n = trim($raw);
            $hq = PharmaHeadquarter::where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($n)])
                ->first();
            if ($hq) {
                return $hq;
            }

            return PharmaHeadquarter::where('company_id', $companyId)
                ->where('name', 'like', '%' . $n . '%')
                ->first();
        };

        $hq = $tryName($draft->headquarter);
        if ($hq) {
            return (int) $hq->id;
        }

        $station = $draft->station ? trim($draft->station) : '';
        if ($station !== '') {
            $hq = $tryName($station);
            if ($hq) {
                return (int) $hq->id;
            }

            $ex = PharmaExstation::where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($station)])
                ->first();
            if ($ex) {
                $linked = $ex->headquarters()->first();
                if ($linked) {
                    return (int) $linked->id;
                }
            }

            $out = PharmaOutstation::where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($station)])
                ->first();
            if ($out) {
                $linked = $out->headquarters()->first();
                if ($linked) {
                    return (int) $linked->id;
                }
            }
        }

        return null;
    }

    /**
     * Minimum header fields present so we can show "Edit draft" (vs incomplete warning only).
     */
    private function isDraftPayloadComplete(?array $payload): bool
    {
        if (empty($payload) || empty($payload['dcr_id'])) {
            return false;
        }

        $hasHeaderContext = ! empty($payload['headquarter_id'])
            || ! empty($payload['headquarter_name'])
            || ! empty($payload['station']);

        return ! empty($payload['report_date'])
            && $hasHeaderContext
            && ! empty($payload['work_status'])
            && ! empty($payload['submitted_to']);
    }

    /**
     * @return array{has_draft: bool, complete: bool, draft_id: int|null, report_date: string|null}
     */
    private function buildDcrDraftResumeInfoFromPayload(?array $payload): array
    {
        $has = ! empty($payload) && ! empty($payload['dcr_id']);

        return [
            'has_draft' => $has,
            'complete' => $this->isDraftPayloadComplete($payload),
            'draft_id' => $has ? (int) $payload['dcr_id'] : null,
            'report_date' => $has ? ($payload['report_date'] ?? null) : null,
        ];
    }

    private function clearDcrFieldVisits(DcrReport $dcr): void
    {
        $dcr->doctorVisits()->delete();
        $dcr->chemistVisits()->delete();
        $dcr->stockistVisits()->delete();
    }

    /**
     * Whether the Close Day POST includes at least one non-empty doctor/chemist/stockist visit row.
     */
    private function requestHasFieldVisitPayload(Request $request): bool
    {
        foreach ($request->input('doctors', []) as $row) {
            if (! empty($row['doctor_id']) || ! empty($row['speciality'])) {
                return true;
            }
        }
        foreach ($request->input('chemists', []) as $row) {
            if (! empty($row['chemist_id']) || ! empty($row['area'])) {
                return true;
            }
        }
        foreach ($request->input('stockists', []) as $row) {
            if (! empty($row['stockist_id']) || ! empty($row['area'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create doctor/chemist/stockist visit rows from Close Day payload. Returns a JSON error response on GPS validation failure.
     */
    private function persistFieldVisitsFromRequest(Request $request, DcrReport $dcr)
    {
        $maxMeters = config('dcr.max_distance_meters', 100);
        $enforceGps = config('dcr.enforce_gps_100m', true);

        if ($request->has('doctors') && is_array($request->doctors)) {
            foreach ($request->doctors as $doctorData) {
                if (! empty($doctorData['doctor_id']) || ! empty($doctorData['speciality'])) {
                    $empLat = isset($doctorData['latitude']) ? (float) $doctorData['latitude'] : null;
                    $empLon = isset($doctorData['longitude']) ? (float) $doctorData['longitude'] : null;
                    $doctorId = $doctorData['doctor_id'] ?? null;
                    if ($doctorId) {
                        $doctor = Doctor::find($doctorId);
                        if ($doctor && $doctor->latitude !== null && $doctor->longitude !== null) {
                            if ($enforceGps && ($empLat === null || $empLon === null)) {
                                return Reply::error(__('GPS location is required for this doctor call. Please capture your location.'));
                            }
                            if ($empLat !== null && $empLon !== null) {
                                $dist = Common::distanceInMeters($doctor->latitude, $doctor->longitude, $empLat, $empLon);
                                if ($enforceGps && $dist > $maxMeters) {
                                    return Reply::error(__('Call allowed only within :meters m of the doctor. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                                }
                            }
                        }
                    }
                    DcrDoctorVisit::create([
                        'dcr_report_id' => $dcr->id,
                        'doctor_id' => $doctorId,
                        'speciality' => $doctorData['speciality'] ?? null,
                        'area' => $doctorData['area'] ?? null,
                        'msl' => $doctorData['msl'] ?? 0,
                        'product1' => $doctorData['product1'] ?? null,
                        'samples_unit1' => $doctorData['samples_unit1'] ?? 0,
                        'pob1' => $doctorData['pob1'] ?? 0,
                        'remark1' => $doctorData['remark1'] ?? null,
                        'product2' => $doctorData['product2'] ?? null,
                        'samples_unit2' => $doctorData['samples_unit2'] ?? 0,
                        'pob2' => $doctorData['pob2'] ?? 0,
                        'remark2' => $doctorData['remark2'] ?? null,
                        'product3' => $doctorData['product3'] ?? null,
                        'samples_unit3' => $doctorData['samples_unit3'] ?? 0,
                        'pob3' => $doctorData['pob3'] ?? 0,
                        'remark3' => $doctorData['remark3'] ?? null,
                        'general_remark' => $doctorData['general_remark'] ?? null,
                        'latitude' => $empLat,
                        'longitude' => $empLon,
                    ]);
                }
            }
        }

        if ($request->has('chemists') && is_array($request->chemists)) {
            foreach ($request->chemists as $chemistData) {
                if (! empty($chemistData['chemist_id']) || ! empty($chemistData['area'])) {
                    $empLat = isset($chemistData['latitude']) ? (float) $chemistData['latitude'] : null;
                    $empLon = isset($chemistData['longitude']) ? (float) $chemistData['longitude'] : null;
                    $chemistId = $chemistData['chemist_id'] ?? null;
                    if ($chemistId) {
                        $chemist = Chemist::find($chemistId);
                        if ($chemist && $chemist->latitude !== null && $chemist->longitude !== null) {
                            if ($enforceGps && ($empLat === null || $empLon === null)) {
                                return Reply::error(__('GPS location is required for this chemist call. Please capture your location.'));
                            }
                            if ($empLat !== null && $empLon !== null) {
                                $dist = Common::distanceInMeters($chemist->latitude, $chemist->longitude, $empLat, $empLon);
                                if ($enforceGps && $dist > $maxMeters) {
                                    return Reply::error(__('Call allowed only within :meters m of the chemist. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                                }
                            }
                        }
                    }
                    DcrChemistVisit::create([
                        'dcr_report_id' => $dcr->id,
                        'chemist_id' => $chemistId,
                        'area' => $chemistData['area'] ?? null,
                        'station' => $chemistData['station'] ?? null,
                        'msl' => $chemistData['msl'] ?? 0,
                        'rcpa1' => $chemistData['rcpa1'] ?? null,
                        'pob_amount1' => $chemistData['pob_amount1'] ?? 0,
                        'remark1' => $chemistData['remark1'] ?? null,
                        'rcpa2' => $chemistData['rcpa2'] ?? null,
                        'pob_amount2' => $chemistData['pob_amount2'] ?? 0,
                        'remark2' => $chemistData['remark2'] ?? null,
                        'rcpa3' => $chemistData['rcpa3'] ?? null,
                        'pob_amount3' => $chemistData['pob_amount3'] ?? 0,
                        'remark3' => $chemistData['remark3'] ?? null,
                        'rcpa4' => $chemistData['rcpa4'] ?? null,
                        'pob_amount4' => $chemistData['pob_amount4'] ?? 0,
                        'remark4' => $chemistData['remark4'] ?? null,
                        'general_remark' => $chemistData['general_remark'] ?? null,
                        'latitude' => $empLat,
                        'longitude' => $empLon,
                    ]);
                }
            }
        }

        if ($request->has('stockists') && is_array($request->stockists)) {
            foreach ($request->stockists as $stockistData) {
                if (! empty($stockistData['stockist_id']) || ! empty($stockistData['area'])) {
                    $empLat = isset($stockistData['latitude']) ? (float) $stockistData['latitude'] : null;
                    $empLon = isset($stockistData['longitude']) ? (float) $stockistData['longitude'] : null;
                    $stockistId = $stockistData['stockist_id'] ?? null;
                    if ($stockistId) {
                        $stockist = Stockist::find($stockistId);
                        if ($stockist && $stockist->latitude !== null && $stockist->longitude !== null) {
                            if ($enforceGps && ($empLat === null || $empLon === null)) {
                                return Reply::error(__('GPS location is required for this stockist call. Please capture your location.'));
                            }
                            if ($empLat !== null && $empLon !== null) {
                                $dist = Common::distanceInMeters($stockist->latitude, $stockist->longitude, $empLat, $empLon);
                                if ($enforceGps && $dist > $maxMeters) {
                                    return Reply::error(__('Call allowed only within :meters m of the stockist. You are :dist m away.', ['meters' => $maxMeters, 'dist' => round($dist)]));
                                }
                            }
                        }
                    }
                    DcrStockistVisit::create([
                        'dcr_report_id' => $dcr->id,
                        'stockist_id' => $stockistId,
                        'area' => $stockistData['area'] ?? null,
                        'station' => $stockistData['station'] ?? null,
                        'msl' => $stockistData['msl'] ?? 0,
                        'pob' => $stockistData['pob'] ?? null,
                        'pob_amount' => $stockistData['pob_amount'] ?? 0,
                        'proprietor' => $stockistData['proprietor'] ?? null,
                        'proprietor_mobile' => $stockistData['proprietor_mobile'] ?? null,
                        'general_remark' => $stockistData['general_remark'] ?? null,
                        'latitude' => $empLat,
                        'longitude' => $empLon,
                    ]);
                }
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'report_date' => 'required|date',
            'work_status' => 'required|string',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'required|exists:users,id',
        ]);
        
        // Optional: add DCR on behalf of another employee (admin or add_dcr_reports all)
        $dcrOwnerId = user()->id;
        $canAddForOthers = user()->hasAdminLikeAccess() || $this->addPermission === 'all';
        if ($canAddForOthers && $request->filled('user_id')) {
            $requestedId = (int) $request->user_id;
            $allowedIds = $this->getAllowedEmployeeIdsForAddDcr();
            if (in_array($requestedId, $allowedIds)) {
                $dcrOwnerId = $requestedId;
            }
        }

        // Validate headquarter is in DCR owner's accessible list
        $dcrOwner = User::find($dcrOwnerId);
        $employeeToValidate = $dcrOwner ?: user();
        if (! $this->headquarterAllowedForDcrContext($employeeToValidate, $request->report_date, (int) $request->headquarter_id)) {
            return Reply::error(__('You can only create DCR for allocated headquarter(s).'));
        }
        
        // SRS 3.2.5: If a draft DCR exists for this user+date (from auto-save), finalize it and mark attendance
        $draft = DcrReport::where('user_id', $dcrOwnerId)
            ->whereDate('report_date', $request->report_date)
            ->where('status', 'draft')
            ->first();
        if ($draft) {
            $fieldWorkTypesEarly = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
            $isFieldWorkDraft = in_array($request->work_status, $fieldWorkTypesEarly, true);
            if (! $isFieldWorkDraft) {
                $request->validate([
                    'remark' => 'required|string',
                ]);
            }

            $headquarter = PharmaHeadquarter::find($request->headquarter_id);
            $headquarterName = $headquarter ? $headquarter->name : null;
            if ($request->station) {
                $hqByName = PharmaHeadquarter::where('name', $request->station)->first();
                if ($hqByName) {
                    $headquarterName = $hqByName->name;
                }
            }
            $workWithString = is_array($request->work_with) ? implode(',', $request->work_with) : (string) $request->work_with;

            DB::beginTransaction();
            try {
                $draft->update([
                    'headquarter' => $headquarterName,
                    'station' => $request->station,
                    'work_status' => $request->work_status,
                    'work_with' => $workWithString,
                    'remark' => $request->remark ?? '',
                    'submitted_to' => $request->submitted_to,
                    'status' => 'pending',
                ]);

                if ($isFieldWorkDraft) {
                    $hasFormVisits = $this->requestHasFieldVisitPayload($request);
                    $hasVisitsInDb = $draft->doctorVisits()->exists()
                        || $draft->chemistVisits()->exists()
                        || $draft->stockistVisits()->exists();
                    if ($hasFormVisits) {
                        $this->clearDcrFieldVisits($draft);
                        $visitError = $this->persistFieldVisitsFromRequest($request, $draft);
                        if ($visitError !== null) {
                            DB::rollBack();

                            return $visitError;
                        }
                    } elseif (! $hasVisitsInDb) {
                        $this->clearDcrFieldVisits($draft);
                    }
                    // If visits exist only in DB (saved via per-visit storeVisit) and the form sends no visit rows, keep DB visits.
                } else {
                    $this->clearDcrFieldVisits($draft);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            if ($draft->submitted_to) {
                $submittedToUser = User::find($draft->submitted_to);
                if ($submittedToUser) {
                    $submittedToUser->notify(new DcrSubmitted($draft));
                }
            }
            $this->markAttendanceFromDcr($dcrOwnerId, $request->report_date);
            return Reply::successWithData(__('DCR Report saved successfully'), ['redirectUrl' => route('dcr-management.index')]);
        }
        
        // If not a field-work type (Working Day / Field Work / Working Days), require remark
        $fieldWorkTypes = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
        if (! in_array($request->work_status, $fieldWorkTypes, true)) {
            $request->validate([
                'remark' => 'required|string',
            ]);
        }
        
        // Get headquarter name
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        $headquarterName = $headquarter ? $headquarter->name : null;
        
        // Determine headquarter from station if station is a HQ name
        if ($request->station) {
            $hqByName = PharmaHeadquarter::where('name', $request->station)->first();
            if ($hqByName) {
                $headquarterName = $hqByName->name;
            }
        }
        
        // HRM Single-Row Structure - One DCR record per date
        // Station is now single select
        $stationString = $request->station; // Single value now
        $workStatusString = $request->work_status; // Single select value
        $workWithString = is_array($request->work_with) ? implode(',', $request->work_with) : $request->work_with;
        
        // Check if a field-work type is selected (shows doctor/chemist/stockist sections)
        $fieldWorkTypes = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
        $isFieldWork = in_array($workStatusString, $fieldWorkTypes, true);
        
        // Base data for all DCR types (user_id = employee for whom DCR is created)
        $dcrData = [
            'company_id' => company()->id,
            'user_id' => $dcrOwnerId,
            'submitted_to' => $request->submitted_to,
            'approved' => false,
            'status' => 'pending',
            'report_date' => $request->report_date,
            'headquarter' => $headquarterName,
            'station' => $stationString,
            'work_status' => $workStatusString,
            'work_with' => $workWithString,
            'remark' => $request->remark, // For non-field work
        ];
        
        // Create the main DCR report
        $dcr = DcrReport::create($dcrData);

        if ($dcr->submitted_to) {
            $submittedToUser = User::find($dcr->submitted_to);
            if ($submittedToUser) {
                $submittedToUser->notify(new DcrSubmitted($dcr));
            }
        }

        if ($isFieldWork) {
            $visitError = $this->persistFieldVisitsFromRequest($request, $dcr);
            if ($visitError !== null) {
                return $visitError;
            }
        }

        // SRS 3.1.3: Field attendance auto-marked when DCR is submitted (Close Day) for the DCR owner
        $this->markAttendanceFromDcr($dcrOwnerId, $request->report_date);

        return Reply::successWithData(__('DCR Report saved successfully'), ['redirectUrl' => route('dcr-management.index')]);
    }

    /**
     * Save DCR as draft (header + optional visit rows from form) without submitting, notifying, or marking attendance.
     */
    public function saveDraft(Request $request)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(! in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'report_date' => 'required|date',
            'work_status' => 'required|string',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'required|exists:users,id',
        ]);

        $dcrOwnerId = user()->id;
        $canAddForOthers = user()->hasAdminLikeAccess() || $this->addPermission === 'all';
        if ($canAddForOthers && $request->filled('user_id')) {
            $requestedId = (int) $request->user_id;
            $allowedIds = $this->getAllowedEmployeeIdsForAddDcr();
            if (in_array($requestedId, $allowedIds)) {
                $dcrOwnerId = $requestedId;
            }
        }

        $dcrOwner = User::find($dcrOwnerId);
        $employeeToValidate = $dcrOwner ?: user();
        if (! $this->headquarterAllowedForDcrContext($employeeToValidate, $request->report_date, (int) $request->headquarter_id)) {
            return Reply::error(__('You can only create DCR for allocated headquarter(s).'));
        }

        $fieldWorkTypes = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
        $isFieldWork = in_array($request->work_status, $fieldWorkTypes, true);
        if (! $isFieldWork) {
            $request->validate([
                'remark' => 'nullable|string',
            ]);
        }

        $dcr = $this->getOrCreateDraft($request, $dcrOwnerId);
        $dcr->refresh();

        DB::beginTransaction();
        try {
            if ($isFieldWork) {
                $hasFormVisits = $this->requestHasFieldVisitPayload($request);
                $hasVisitsInDb = $dcr->doctorVisits()->exists()
                    || $dcr->chemistVisits()->exists()
                    || $dcr->stockistVisits()->exists();
                if ($hasFormVisits) {
                    $this->clearDcrFieldVisits($dcr);
                    $visitError = $this->persistFieldVisitsFromRequest($request, $dcr);
                    if ($visitError !== null) {
                        DB::rollBack();

                        return $visitError;
                    }
                } elseif (! $hasVisitsInDb) {
                    $this->clearDcrFieldVisits($dcr);
                }
            } else {
                $this->clearDcrFieldVisits($dcr);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return Reply::successWithData(__('Draft saved. You can submit when ready.'), [
            'dcr_id' => $dcr->id,
        ]);
    }

    /**
     * Update an existing header-only DCR (add visit lines and/or adjust header fields).
     */
    public function update(Request $request, $id)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(! $this->userCanAddOrEditDcrReports());

        $dcr = DcrReport::where('company_id', company()->id)->findOrFail($id);
        abort_403(!$this->canViewDcr($dcr));
        abort_403($dcr->status === 'approved' || $dcr->approved);

        $request->validate([
            'report_date' => 'required|date',
            'work_status' => 'required|string',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'required|exists:users,id',
        ]);

        $dcrOwnerId = (int) $dcr->user_id;
        $dcrOwner = User::find($dcrOwnerId);
        $employeeToValidate = $dcrOwner ?: user();
        if (! $this->headquarterAllowedForDcrContext($employeeToValidate, $request->report_date, (int) $request->headquarter_id)) {
            return Reply::error(__('You can only create DCR for allocated headquarter(s).'));
        }

        $duplicate = DcrReport::where('company_id', company()->id)
            ->where('user_id', $dcrOwnerId)
            ->whereDate('report_date', $request->report_date)
            ->where('id', '!=', $dcr->id)
            ->exists();
        if ($duplicate) {
            return Reply::error(__('Another DCR already exists for this employee on the selected date.'));
        }

        $fieldWorkTypes = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
        if (! in_array($request->work_status, $fieldWorkTypes, true)) {
            $request->validate([
                'remark' => 'required|string',
            ]);
        }

        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        $headquarterName = $headquarter ? $headquarter->name : null;
        if ($request->station) {
            $hqByName = PharmaHeadquarter::where('name', $request->station)->first();
            if ($hqByName) {
                $headquarterName = $hqByName->name;
            }
        }
        $stationString = $request->station;
        $workStatusString = $request->work_status;
        $workWithString = is_array($request->work_with) ? implode(',', $request->work_with) : $request->work_with;
        $isFieldWork = in_array($workStatusString, $fieldWorkTypes, true);

        DB::beginTransaction();
        try {
            $dcr->update([
                'submitted_to' => $request->submitted_to,
                'report_date' => $request->report_date,
                'headquarter' => $headquarterName,
                'station' => $stationString,
                'work_status' => $workStatusString,
                'work_with' => $workWithString,
                'remark' => $request->remark ?? '',
            ]);

            $this->clearDcrFieldVisits($dcr);
            if ($isFieldWork) {
                $visitError = $this->persistFieldVisitsFromRequest($request, $dcr);
                if ($visitError !== null) {
                    DB::rollBack();

                    return $visitError;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->markAttendanceFromDcr($dcrOwnerId, $request->report_date);

        return Reply::successWithData(__('DCR Report saved successfully'), ['redirectUrl' => route('dcr-management.index')]);
    }

    /**
     * Create or update attendance for the employee on report date when DCR is submitted.
     * SRS 3.1.3: Field employees - Attendance auto-marked only after DCR Close Day; date must match DCR date.
     */
    private function markAttendanceFromDcr(int $userId, string $reportDate): void
    {
        $date = Carbon::parse($reportDate, company()->timezone);
        $dateStr = $date->format('Y-m-d');

        $startOfDay = $date->copy()->startOfDay()->timezone(config('app.timezone'));
        $endOfDay = $date->copy()->endOfDay()->timezone(config('app.timezone'));

        $existing = Attendance::where('user_id', $userId)
            ->whereBetween('clock_in_time', [$startOfDay, $endOfDay])
            ->first();

        if ($existing) {
            $existing->work_from_type = 'other';
            $existing->working_from = 'Field - DCR';
            $existing->last_updated_by = user()->id;
            $existing->save();
            return;
        }

        $defaultShift = function_exists('attendance_setting') ? optional(attendance_setting())->shift : null;
        $officeStart = $defaultShift && !empty($defaultShift->office_start_time)
            ? $defaultShift->office_start_time
            : '09:00:00';
        $officeEnd = $defaultShift && !empty($defaultShift->office_end_time)
            ? $defaultShift->office_end_time
            : '18:00:00';

        $clockIn = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $officeStart, company()->timezone)
            ->timezone(config('app.timezone'));
        $clockOut = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $officeEnd, company()->timezone)
            ->timezone(config('app.timezone'));

        Attendance::create([
            'user_id' => $userId,
            'company_id' => company()->id,
            'clock_in_time' => $clockIn,
            'clock_out_time' => $clockOut,
            'clock_in_ip' => request()->ip(),
            'clock_out_ip' => request()->ip(),
            'working_from' => 'Field - DCR',
            'work_from_type' => 'other',
            'late' => 'no',
            'half_day' => 'no',
            'added_by' => user()->id,
            'employee_shift_id' => $defaultShift?->id ?? null,
        ]);
    }

    public function destroy($id)
    {
        $report = DcrReport::with('user.employeeDetail', 'user.employeeDetails')->findOrFail($id);
        $this->deletePermission = user()->permission('delete_dcr_reports');
        abort_403(!in_array($this->deletePermission, ['all', 'added', 'owned', 'both']));
        abort_403(!$this->canViewDcr($report));

        $report->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }
    
    /**
     * AJAX: Create new doctor from DCR form and return the created doctor
     */
    public function createDoctorInline(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'speciality' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $this->ensureInlineHeadquarterAccessible((int) $request->headquarter_id);
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $doctor = new Doctor();
        $doctor->company_id = company()->id;
        $doctor->fullname = $request->fullname;
        $doctor->email = $request->email;
        $doctor->mobile = $request->mobile;
        $doctor->qualification = $request->qualification;
        $doctor->speciality = $request->speciality;
        $doctor->headquarter_id = $request->headquarter_id;
        $doctor->area_id = $headquarter->area_id;
        $doctor->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $doctor->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $doctor->outstation_id = $request->outstation_id;
        }
        
        $doctor->save();
        $doctor->load(['area', 'headquarter', 'exstation', 'outstation']);

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Doctor created successfully',
            'doctor' => [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'speciality' => $doctor->speciality,
                'msl_number' => $doctor->msl_number,
                'mobile' => $doctor->mobile,
                'qualification' => $doctor->qualification,
                'headquarter_id' => $doctor->headquarter_id,
                'exstation_id' => $doctor->exstation_id,
                'outstation_id' => $doctor->outstation_id,
                'area' => $doctor->area ? ['id' => $doctor->area->id, 'name' => $doctor->area->name] : null,
                'area_name' => optional($doctor->area)->name,
                'headquarter' => $doctor->headquarter ? ['id' => $doctor->headquarter->id, 'name' => $doctor->headquarter->name] : null,
                'exstation' => $doctor->exstation ? ['id' => $doctor->exstation->id, 'name' => $doctor->exstation->name] : null,
                'outstation' => $doctor->outstation ? ['id' => $doctor->outstation->id, 'name' => $doctor->outstation->name] : null,
            ],
        ]);
    }
    
    /**
     * AJAX: Create new chemist from DCR form and return the created chemist
     */
    public function createChemistInline(Request $request)
    {
        $request->validate([
            'shopname' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $this->ensureInlineHeadquarterAccessible((int) $request->headquarter_id);
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $chemist = new Chemist();
        $chemist->company_id = company()->id;
        $chemist->shopname = $request->shopname;
        $chemist->email = $request->email;
        $chemist->mobile = $request->mobile;
        $chemist->headquarter_id = $request->headquarter_id;
        $chemist->area_id = $headquarter->area_id;
        $chemist->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $chemist->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $chemist->outstation_id = $request->outstation_id;
        }
        
        $chemist->save();

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Chemist created successfully',
            'chemist' => [
                'id' => $chemist->id,
                'shopname' => $chemist->shopname,
                'area' => $chemist->area,
                'mobile' => $chemist->mobile,
            ]
        ]);
    }
    
    /**
     * AJAX: Create new stockist from DCR form and return the created stockist
     */
    public function createStockistInline(Request $request)
    {
        $request->validate([
            'shopname' => 'required|string|max:255',
            'owner_mobile' => 'required|string|max:20',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $this->ensureInlineHeadquarterAccessible((int) $request->headquarter_id);
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $stockist = new Stockist();
        $stockist->company_id = company()->id;
        $stockist->shopname = $request->shopname;
        $stockist->owner_name = $request->owner_name;
        $stockist->owner_mobile = $request->owner_mobile;
        $stockist->headquarter_id = $request->headquarter_id;
        $stockist->area_id = $headquarter->area_id;
        $stockist->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $stockist->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $stockist->outstation_id = $request->outstation_id;
        }
        
        $stockist->save();

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Stockist created successfully',
            'stockist' => [
                'id' => $stockist->id,
                'shopname' => $stockist->shopname,
                'area' => $stockist->area,
                'owner_mobile' => $stockist->owner_mobile,
            ]
        ]);
    }

    private function ensureInlineHeadquarterAccessible(int $headquarterId): void
    {
        $allowedHqIds = $this->accessibleHeadquarterIds(user());

        if ($allowedHqIds === null) {
            return;
        }

        abort_403(!in_array($headquarterId, array_map('intval', $allowedHqIds), true));
    }
    
    /**
     * AND this onto queries that already pin station (DCR AJAX). Matches
     * AccessibleHeadquarters::applyCustomerHeadquarterOrAreaScope so this endpoint still works if production
     * shipped an older trait without that method (avoids HTTP 500 on dcr-get-station-customers).
     */
    private function applyDcrStationCustomerAccessScope(Builder $query, ?array $hqIds, ?array $areaIds): void
    {
        $areaIds = array_values(array_filter($areaIds ?? [], static function ($id) {
            return $id !== null && $id !== '';
        }));
        $hqIds = array_values(array_filter($hqIds ?? [], static function ($id) {
            return $id !== null && $id !== '';
        }));

        if (! empty($hqIds)) {
            $query->where(function ($q) use ($hqIds, $areaIds) {
                $q->whereIn('headquarter_id', $hqIds);
                if (! empty($areaIds)) {
                    $q->orWhereIn('area_id', $areaIds);
                }
            });

            return;
        }

        if (! empty($areaIds)) {
            $query->whereIn('area_id', $areaIds);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * AJAX: Get doctors, chemists, and stockists filtered by station
     * station_type: "headquarter" (from DCR create UI), "hq" (legacy), "exstation", "outstation"
     */
    public function getStationCustomers(Request $request)
    {
        $stationType = $request->get('station_type');
        $stationId = $request->get('station_id');

        $doctors = collect();
        $chemists = collect();
        $stockists = collect();

        if ($stationType && $stationId) {
            $isHeadquarterStation = ($stationType === 'hq' || $stationType === 'headquarter');

            $doctorQuery = Doctor::with(['area', 'headquarter', 'exstation', 'outstation'])
                ->where('company_id', company()->id);

            $chemistQuery = Chemist::with(['area', 'headquarter', 'exstation', 'outstation'])
                ->where('company_id', company()->id);

            $stockistQuery = Stockist::with(['area', 'headquarter', 'exstation', 'outstation'])
                ->where('company_id', company()->id);

            if ($isHeadquarterStation) {
                $doctorQuery->where('headquarter_id', $stationId)
                    ->whereNull('exstation_id')
                    ->whereNull('outstation_id');
                $chemistQuery->where('headquarter_id', $stationId)
                    ->whereNull('exstation_id')
                    ->whereNull('outstation_id');
                $stockistQuery->where('headquarter_id', $stationId)
                    ->whereNull('exstation_id')
                    ->whereNull('outstation_id');
            } elseif ($stationType === 'exstation') {
                $doctorQuery->where('exstation_id', $stationId);
                $chemistQuery->where('exstation_id', $stationId);
                $stockistQuery->where('exstation_id', $stationId);
            } elseif ($stationType === 'outstation') {
                $doctorQuery->where('outstation_id', $stationId);
                $chemistQuery->where('outstation_id', $stationId);
                $stockistQuery->where('outstation_id', $stationId);
            } else {
                $doctorQuery->whereRaw('1 = 0');
                $chemistQuery->whereRaw('1 = 0');
                $stockistQuery->whereRaw('1 = 0');
            }

            $accessibleHqIds = $this->accessibleHeadquarterIds();
            $accessibleAreaIds = $this->accessibleAreaIds();
            if ($accessibleHqIds !== null && ! user()->hasAdminLikeAccess()) {
                [$effectiveHqIds, $effectiveAreaIds] = $this->expandStationCustomerAccessibleScopeForTour(
                    $request,
                    (string) $stationType,
                    $stationId,
                    $accessibleHqIds,
                    $accessibleAreaIds ?? []
                );
                $this->applyDcrStationCustomerAccessScope($doctorQuery, $effectiveHqIds, $effectiveAreaIds);
                $this->applyDcrStationCustomerAccessScope($chemistQuery, $effectiveHqIds, $effectiveAreaIds);
                $this->applyDcrStationCustomerAccessScope($stockistQuery, $effectiveHqIds, $effectiveAreaIds);
            }

            $doctors = $doctorQuery->get();
            $chemists = $chemistQuery->get();
            $stockists = $stockistQuery->get();
        }
        
        $mapDoctorForJs = function ($doctor) {
            return [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'mobile' => $doctor->mobile,
                'speciality' => $doctor->speciality,
                'msl_number' => $doctor->getAttribute('msl_number'),
                'headquarter_id' => $doctor->headquarter_id,
                'exstation_id' => $doctor->exstation_id,
                'outstation_id' => $doctor->outstation_id,
                'area' => $doctor->area ? ['id' => $doctor->area->id, 'name' => $doctor->area->name] : null,
                'area_name' => optional($doctor->area)->name,
                'headquarter' => $doctor->headquarter ? ['id' => $doctor->headquarter->id, 'name' => $doctor->headquarter->name] : null,
                'exstation' => $doctor->exstation ? ['id' => $doctor->exstation->id, 'name' => $doctor->exstation->name] : null,
                'outstation' => $doctor->outstation ? ['id' => $doctor->outstation->id, 'name' => $doctor->outstation->name] : null,
            ];
        };

        return Reply::dataOnly([
            'doctors' => $doctors->map($mapDoctorForJs)->toArray(),
            'chemists' => $chemists->map(function ($chemist) {
                return [
                    'id' => $chemist->id,
                    'shopname' => $chemist->shopname,
                    'fullname' => $chemist->fullname,
                    'mobile' => $chemist->mobile,
                    'headquarter_id' => $chemist->headquarter_id,
                    'exstation_id' => $chemist->exstation_id,
                    'outstation_id' => $chemist->outstation_id,
                    'area' => $chemist->area ? ['id' => $chemist->area->id, 'name' => $chemist->area->name] : null,
                    'area_name' => optional($chemist->area)->name,
                    'headquarter' => $chemist->headquarter ? ['id' => $chemist->headquarter->id, 'name' => $chemist->headquarter->name] : null,
                    'exstation' => $chemist->exstation ? ['id' => $chemist->exstation->id, 'name' => $chemist->exstation->name] : null,
                    'outstation' => $chemist->outstation ? ['id' => $chemist->outstation->id, 'name' => $chemist->outstation->name] : null,
                ];
            })->toArray(),
            'stockists' => $stockists->map(function ($stockist) {
                return [
                    'id' => $stockist->id,
                    'shopname' => $stockist->shopname,
                    'owner_name' => $stockist->owner_name,
                    'owner_mobile' => $stockist->owner_mobile,
                    'headquarter_id' => $stockist->headquarter_id,
                    'exstation_id' => $stockist->exstation_id,
                    'outstation_id' => $stockist->outstation_id,
                    'area' => $stockist->area ? ['id' => $stockist->area->id, 'name' => $stockist->area->name] : null,
                    'area_name' => optional($stockist->area)->name,
                    'headquarter' => $stockist->headquarter ? ['id' => $stockist->headquarter->id, 'name' => $stockist->headquarter->name] : null,
                    'exstation' => $stockist->exstation ? ['id' => $stockist->exstation->id, 'name' => $stockist->exstation->name] : null,
                    'outstation' => $stockist->outstation ? ['id' => $stockist->outstation->id, 'name' => $stockist->outstation->name] : null,
                ];
            })->toArray(),
        ]);
    }
    
    /**
     * Approve a single DCR report
     */
    public function approve($id)
    {
        $dcr = DcrReport::with('user.employeeDetail', 'user.employeeDetails')->findOrFail($id);
        
        // Check if user can approve based on hierarchy:
        // 1. Admin with 'all' permission
        // 2. DCR is submitted to current user
        // 3. Current user is the reporting manager of the DCR creator (hierarchy-based)
        $approvePermission = user()->permission('approve_dcr_reports');
        $isAdmin = $approvePermission == 'all';

        $descendantIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
        $canApprove = $isAdmin || $dcr->submitted_to == user()->id
            || in_array((int) $dcr->user_id, array_map('intval', $descendantIds), true);

        abort_403(! $canApprove);
        abort_403(!$this->canViewDcr($dcr));
        
        $before = $dcr->only(['approved', 'approved_by', 'approved_at', 'status']);
        $dcr->approved = true;
        $dcr->approved_by = user()->id;
        $dcr->approved_at = now();
        $dcr->status = 'approved';
        $dcr->save();
        EnterpriseAudit::record('dcr_report.approved', $dcr, $before, $dcr->only(['approved', 'approved_by', 'approved_at', 'status']));

        return Reply::success(__('DCR report approved successfully'));
    }
    
    /**
     * Reject a single DCR report
     */
    public function reject($id)
    {
        $dcr = DcrReport::with('user.employeeDetail', 'user.employeeDetails')->findOrFail($id);
        
        // Check if user can approve based on hierarchy:
        // 1. Admin with 'all' permission
        // 2. DCR is submitted to current user
        // 3. Current user is the reporting manager of the DCR creator (hierarchy-based)
        $approvePermission = user()->permission('approve_dcr_reports');
        $isAdmin = $approvePermission == 'all';

        $descendantIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);
        $canApprove = $isAdmin || $dcr->submitted_to == user()->id
            || in_array((int) $dcr->user_id, array_map('intval', $descendantIds), true);

        abort_403(! $canApprove);
        abort_403(!$this->canViewDcr($dcr));
        
        $before = $dcr->only(['approved', 'approved_by', 'approved_at', 'status']);
        $dcr->approved = false;
        $dcr->approved_by = user()->id;
        $dcr->approved_at = now();
        $dcr->status = 'rejected';
        $dcr->save();
        EnterpriseAudit::record('dcr_report.rejected', $dcr, $before, $dcr->only(['approved', 'approved_by', 'approved_at', 'status']), [], 'warning');

        return Reply::success(__('DCR report rejected'));
    }
    
    /**
     * Approve multiple DCR reports
     */
    public function approveAll(Request $request)
    {
        $this->approvePermission = user()->permission('approve_dcr_reports');
        $isAdmin = $this->approvePermission == 'all';
        
        $request->validate([
            'dcr_ids' => 'required|array',
            'dcr_ids.*' => 'required|integer|exists:dcr_reports,id'
        ]);
        
        $dcrIds = $request->dcr_ids;
        
        $reportingEmployeeIds = RoleHierarchy::reportingDescendantUserIds(user()->id, company()->id);

        if (! $isAdmin) {
            $dcrsAccessible = DcrReport::whereIn('id', $dcrIds)
                ->where(function($q) use ($reportingEmployeeIds) {
                    $q->where('submitted_to', user()->id);
                    if (! empty($reportingEmployeeIds)) {
                        $q->orWhereIn('user_id', $reportingEmployeeIds);
                    }
                })
                ->count();
            abort_403($dcrsAccessible == 0);
        }
        
        // Approve all DCRs (admin can approve any, reporting manager can approve DCRs submitted to them or from their reports)
        $query = DcrReport::whereIn('id', $dcrIds)
            ->where('company_id', company()->id)
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status');
            });
        
        // If not admin, only approve DCRs submitted to current user OR from reporting employees
        if (!$isAdmin) {
            $query->where(function($q) use ($reportingEmployeeIds) {
                $q->where('submitted_to', user()->id);
                if (!empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
        }
        
        $dcrsToApprove = (clone $query)->get(['id', 'approved', 'approved_by', 'approved_at', 'status']);
        $query->update([
            'approved' => true,
            'approved_by' => user()->id,
            'approved_at' => now(),
            'status' => 'approved'
        ]);
        foreach ($dcrsToApprove as $dcr) {
            EnterpriseAudit::record('dcr_report.bulk_approved', $dcr, $dcr->only(['approved', 'approved_by', 'approved_at', 'status']), [
                'approved' => true,
                'approved_by' => user()->id,
                'status' => 'approved',
            ]);
        }
        
        return Reply::success(__('All DCR reports approved successfully'));
    }
}
