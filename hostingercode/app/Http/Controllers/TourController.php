<?php

namespace App\Http\Controllers;

use App\Helper\EmployeeSelectLabel;
use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\Tour;
use App\Models\TourMonthLock;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaExstation;
use App\Models\PharmaOutstation;
use App\Models\User;
use App\Models\PharmaArea;
use App\Notifications\TourPlanApproved;
use App\Notifications\TourPlanSubmitted;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Traits\AccessibleHeadquarters;

class TourController extends AccountBaseController
{
    use AccessibleHeadquarters;
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Tour Plan';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tours', $this->user->modules));
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $this->viewPermission = user()->permission('view_tours');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $this->userHeadquarter = user()->employeeDetails->headquarter_id 
            ?? user()->employeeDetails->pharma_headquarter_id 
            ?? null;
        
        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        
        // Load specific designations for "Work With" dropdown (same as expense form)
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
        
        $this->currentMonth = now()->format('Y-m');
        
        // For admin: employee filter - MUST be defined BEFORE using it
        $selectedEmployeeId = $request->get('employee_id');
        $this->selectedEmployeeId = $selectedEmployeeId;
        
        // Load employees as User models (dropdown labels use EmployeeSelectLabel: employee_id - name (designation))
        $employeeQueryWith = ['employeeDetail.headquarter', 'employeeDetails.headquarter', 'employeeDetail.designation', 'employeeDetails.designation'];
        if ($this->viewPermission == 'all') {
            $this->employees = User::with($employeeQueryWith)
                ->whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->orderBy('name')
                ->get();
        } else {
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $submittedEmployeeIds = Tour::where('submitted_to', user()->id)
                ->where('company_id', company()->id)
                ->distinct()
                ->pluck('user_id')
                ->toArray();
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingInViewableScope = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
            $employeeIds = array_values(array_unique(array_merge($submittedEmployeeIds, $reportingInViewableScope)));

            if (! empty($employeeIds)) {
                $this->employees = User::with($employeeQueryWith)
                    ->whereHas('employeeDetail')
                    ->where('company_id', company()->id)
                    ->whereIn('id', $employeeIds)
                    ->orderBy('name')
                    ->get();
            } else {
                $this->employees = collect();
            }
        }

        $this->employeesJson = $this->employees->map(function (User $employee) {
            $empDetail = $employee->employeeDetail ?? $employee->employeeDetails;

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'designation' => $empDetail && $empDetail->designation ? $empDetail->designation->name : null,
                'headquarter_id' => $empDetail->headquarter_id ?? null,
                'headquarter_name' => $empDetail && $empDetail->headquarter ? $empDetail->headquarter->name : null,
                'employee_id' => $empDetail->employee_id ?? null,
                'label' => EmployeeSelectLabel::plain($employee),
            ];
        })->values();
        
        // Get managers/supervisors for "Submit To" dropdown
        $this->managers = User::allEmployees()->where('id', '!=', user()->id);
        
        // Get selected employee's headquarter if employee filter is set
        $selectedEmployeeHeadquarter = null;
        if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
            $selectedEmployee = User::with(['employeeDetail.headquarter', 'employeeDetails.headquarter'])
                ->find($selectedEmployeeId);
            if ($selectedEmployee) {
                $empDetail = $selectedEmployee->employeeDetail ?? $selectedEmployee->employeeDetails;
                $selectedEmployeeHeadquarter = $empDetail->headquarter_id ?? null;
            }
        }
        $this->selectedEmployeeHeadquarter = $selectedEmployeeHeadquarter;
        
        // Filter headquarters based on employee assignment and area mapping
        // For ABM/Area Business Manager: show all headquarters mapped to their assigned areas
        // For admin with selected employee: show headquarters based on that employee's areas
        // For non-admin: show headquarters based on their areas
        // For admin with no employee selected: show all headquarters
        $headquarterQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area']);
        
        // Determine which employee to check (selected employee or current user)
        $employeeToCheck = null;
        
        if ($this->viewPermission == 'all') {
            // Admin user
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                // Admin viewing specific employee: check that employee's areas
                // Note: areas and regions are JSON columns, not relationships, so don't eager load them
                $employeeToCheck = User::with(['employeeDetail.headquarter', 'employeeDetails.headquarter', 'employeeDetail.designation'])
                    ->find($selectedEmployeeId);
            }
            // Admin with no employee selected: show all headquarters (no filtering)
        } else {
            // Non-admin: check current user's areas
            $employeeToCheck = user();
        }
        
        // If we have an employee to check, filter by their accessible headquarters
        if ($employeeToCheck) {
            // Use the AccessibleHeadquarters trait method to get correct headquarters
            // Pass the employee to check (for admin viewing specific employee's tours)
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
        // If no employee to check (admin with no selection), show all - no filtering
        
        $this->headquarters = $headquarterQuery->get();

        // Mirror TourController::create(): ABM/RBM may have no single headquarter_id on profile but mapped HQs
        if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
            $this->userHeadquarter = $this->headquarters->first()->id;
        }

        // Effective HQ for readonly display / calendar filter (no dropdown on index)
        $this->tourPlanEffectiveHeadquarter = null;
        if ($this->headquarters->isNotEmpty()) {
            $effectiveId = null;
            if ($selectedEmployeeId && $selectedEmployeeId != 'all' && $this->selectedEmployeeHeadquarter) {
                $cand = (int) $this->selectedEmployeeHeadquarter;
                if ($this->headquarters->contains('id', $cand)) {
                    $effectiveId = $cand;
                }
            }
            if ($effectiveId === null && $this->userHeadquarter) {
                $cand = (int) $this->userHeadquarter;
                if ($this->headquarters->contains('id', $cand)) {
                    $effectiveId = $cand;
                }
            }
            if ($effectiveId === null) {
                $effectiveId = (int) $this->headquarters->first()->id;
            }
            $this->tourPlanEffectiveHeadquarter = $this->headquarters->firstWhere('id', $effectiveId)
                ?? $this->headquarters->first();
        }

        // Load tours - include all relevant data including employee's headquarter and work_with employees
        // Use both employeeDetail and employeeDetails to handle different relationship names
        $toursQuery = Tour::with([
            'user.employeeDetail.headquarter',
            'user.employeeDetails.headquarter', 
            'headquarter', 
            'approvedBy', 
            'submittedTo'
        ]);
        
        // Get accessible area IDs for filtering tours by area
        $accessibleAreaIds = $this->accessibleAreaIds();
        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();
        
        // APPROVAL PAGE: Show tours submitted TO the current user for approval
        // This page is for managers/admins to approve tours submitted to them
        if ($this->viewPermission == 'all') {
            // Admin / view-all: restrict by hierarchy and (for non-admin) by HQ scope
            $toursQuery = $toursQuery->where('company_id', company()->id);
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            if (!empty($viewableIds)) {
                $toursQuery = $toursQuery->whereIn('user_id', $viewableIds);
            }
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $toursQuery = $toursQuery->where('user_id', $selectedEmployeeId);
            }
            // Non-admin with 'all' permission: restrict by accessible HQs (Requirement 3.1.1)
            if (!user()->hasRole('admin') && $accessibleHqIdsForFilter !== null) {
                if (!empty($accessibleHqIdsForFilter)) {
                    $toursQuery->whereIn('headquarter_id', $accessibleHqIdsForFilter);
                } else {
                    $toursQuery->whereRaw('1 = 0');
                }
            }
        } else {
            // Non-admin (reporting manager):
            // A) Approval inbox: tours submitted TO me — always visible (no RoleHierarchy / HQ choke on creator).
            // B) Team visibility: tours from direct reports — scoped by viewableIds + accessible headquarters.
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIdsInScope = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

            $toursQuery = $toursQuery->where('company_id', company()->id)
                ->where(function ($q) use ($reportingEmployeeIdsInScope, $accessibleHqIdsForFilter) {
                    $q->where('submitted_to', user()->id);

                    if (!empty($reportingEmployeeIdsInScope)) {
                        $q->orWhere(function ($team) use ($reportingEmployeeIdsInScope, $accessibleHqIdsForFilter) {
                            $team->whereIn('user_id', $reportingEmployeeIdsInScope);
                            if ($accessibleHqIdsForFilter !== null) {
                                if (!empty($accessibleHqIdsForFilter)) {
                                    $team->whereIn('headquarter_id', $accessibleHqIdsForFilter);
                                } else {
                                    $team->whereRaw('1 = 0');
                                }
                            }
                        });
                    }
                });

            // If employee filter is set, filter by that employee
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $toursQuery = $toursQuery->where('user_id', $selectedEmployeeId);
            }
        }
        
        $this->tours = $toursQuery->orderBy('date', 'asc')->get();
        
        if (config('app.debug')) {
            \Log::info('Tour Query Debug', [
                'user_id' => user()->id,
                'user_name' => user()->name,
                'view_permission' => $this->viewPermission,
                'selected_employee_id' => $selectedEmployeeId,
                'tours_count' => $this->tours->count(),
                'tours' => $this->tours->map(function($tour) {
                    return [
                        'id' => $tour->id,
                        'user_id' => $tour->user_id,
                        'user_name' => $tour->user->name ?? 'N/A',
                        'submitted_to' => $tour->submitted_to,
                        'date' => $tour->date,
                        'status' => $tour->status,
                    ];
                })->toArray(),
            ]);
        }

        // Check if user has any tours submitted to them (to show/hide menu item)
        $this->hasToursToApprove = Tour::where('submitted_to', user()->id)
            ->where('company_id', company()->id)
            ->exists();

        // Locked months (for admin: show list and Unlock button)
        $this->lockedMonths = TourMonthLock::where('company_id', company()->id)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return view('tours.index', $this->data);
    }

    public function create(Request $request)
    {
        $this->addPermission = user()->permission('add_tours');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        // Admin can create tour plan for another employee via ?for_employee_id=
        $forEmployeeId = $request->get('for_employee_id');
        $targetUserId = user()->id;
        if (user()->hasRole('admin') && $forEmployeeId) {
            $targetUser = User::where('company_id', company()->id)
                ->whereHas('employeeDetail')
                ->find($forEmployeeId);
            if ($targetUser) {
                $targetUserId = (int) $targetUser->id;
            }
        }
        $this->targetUserId = $targetUserId;
        $this->targetUser = User::find($targetUserId);

        // When admin creates for another employee, use target's allocation; otherwise current user's
        $employeeForAllocation = ($this->targetUser && $targetUserId != user()->id) ? $this->targetUser : user();
        $empDetail = $employeeForAllocation->employeeDetail ?? $employeeForAllocation->employeeDetails ?? null;
        $this->userHeadquarter = $empDetail ? ($empDetail->headquarter_id ?? $empDetail->pharma_headquarter_id ?? null) : null;

        // For non-admin: load headquarter with mapped area so the view can show "mapped area"
        $this->userHeadquarterWithArea = $this->userHeadquarter
            ? PharmaHeadquarter::with('area')->find($this->userHeadquarter)
            : null;
        
        // Get accessible headquarters for filtering (use target employee when admin creates for someone else)
        $accessibleHqIds = $this->accessibleHeadquarterIds($employeeForAllocation);
        
        if (config('app.debug')) {
            \Log::info('TourController::create - Debug Info', [
                'user_id' => user()->id,
                'user_name' => user()->name,
                'is_admin' => user()->hasRole('admin'),
                'accessible_hq_ids' => $accessibleHqIds,
                'accessible_hq_count' => is_array($accessibleHqIds) ? count($accessibleHqIds) : 'null',
                'employee_details' => [
                    'areas_raw' => user()->employeeDetails->areas ?? null,
                    'areas_type' => gettype(user()->employeeDetails->areas ?? null),
                    'areas_decoded' => is_string(user()->employeeDetails->areas ?? null)
                        ? json_decode(user()->employeeDetails->areas, true)
                        : user()->employeeDetails->areas ?? null,
                    'regions_raw' => user()->employeeDetails->regions ?? null,
                ],
            ]);
        }

        // Load headquarters with their stations for dropdown/auto-population
        // Filter by accessibleHeadquarterIds: null = all (admin/HR/PMT/Sales Manager); empty = none; array = only those HQs
        $headquartersQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id);

        if ($accessibleHqIds === null) {
            // Admin, HR, PMT, Sales Manager: show all headquarters
            $this->headquarters = $headquartersQuery->get();
        } elseif (empty($accessibleHqIds)) {
            // No allocated headquarter/area/region/zone
            $this->headquarters = collect();
        } else {
            // MR, ABM, RBM, ZM: show only allocated headquarters
            $this->headquarters = $headquartersQuery->whereIn('id', $accessibleHqIds)->get();
        }

        // Show dropdown when user has multiple HQs to choose from (ABM/RBM/ZM with many areas)
        $this->showHqDropdownForPharmaRoles = $this->headquarters->count() > 1;

        // For ABM/RBM/ZM with single HQ: headquarter_id is null but accessibleHeadquarterIds returns 1 HQ
        if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
            $this->userHeadquarter = $this->headquarters->first()->id;
            $this->userHeadquarterWithArea = PharmaHeadquarter::with('area')->find($this->userHeadquarter);
        }

        if (config('app.debug')) {
            \Log::info('TourController::create - Final Headquarters', [
                'user_id' => user()->id,
                'headquarters_count' => $this->headquarters->count(),
                'headquarters_ids' => $this->headquarters->pluck('id')->toArray(),
                'headquarters_names' => $this->headquarters->pluck('name')->toArray(),
            ]);
        }

        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        
        // Load specific designations for "Work With" dropdown (same as expense form and approval page)
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
        
        // All employees as User models (dropdown uses EmployeeSelectLabel via x-user-option)
        $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter', 'employeeDetails.designation', 'employeeDetails.headquarter'])
            ->whereHas('employeeDetail')
            ->where('company_id', company()->id)
            ->orderBy('name')
            ->get();

        $this->employeesJson = $this->employees->map(function (User $employee) {
            $empDetail = $employee->employeeDetail ?? $employee->employeeDetails;

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'designation' => $empDetail && $empDetail->designation ? $empDetail->designation->name : null,
                'headquarter_id' => $empDetail->headquarter_id ?? null,
                'headquarter_name' => $empDetail && $empDetail->headquarter ? $empDetail->headquarter->name : null,
                'employee_id' => $empDetail->employee_id ?? null,
                'label' => EmployeeSelectLabel::plain($employee),
            ];
        })->values();
        
        // Get employee's reporting manager from HR (Tour plan must be submitted to Reporting Manager only)
        $targetEmployeeDetails = $this->targetUser ? ($this->targetUser->employeeDetails ?? $this->targetUser->employeeDetail) : null;
        $this->reportingManagerId = $targetEmployeeDetails ? ($targetEmployeeDetails->reporting_to ?? null) : optional(user()->employeeDetails)->reporting_to;
        
        // "Submit To" dropdown: only the target employee's reporting manager (like DCR)
        if ($this->reportingManagerId) {
            $this->managers = User::with(['employeeDetail.designation'])
                ->whereHas('employeeDetail')
                ->where('id', $this->reportingManagerId)
                ->where('company_id', company()->id)
                ->get();
        } else {
            $this->managers = collect();
        }
        
        $this->currentMonth = now()->format('Y-m');
        
        // Load existing tours for target user (current user or selected employee when admin)
        $this->existingTours = Tour::with(['user', 'headquarter', 'workingWith', 'approvedBy', 'submittedTo'])
            ->where('user_id', $targetUserId)
            ->orderBy('date', 'asc')
            ->get();

        if (request()->ajax()) {
            $html = view('tours.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('tours.create', $this->data);
    }

    /**
     * Resolve tour headquarter_id from station (HQ name, ex-, or out-station).
     * When an ex/out station is linked to multiple HQs, prefers the HQ from the form when it matches a link.
     */
    protected function resolveTourHeadquarterIdFromStation(?string $station, ?int $requestHeadquarterId, $targetEmpDetails): ?int
    {
        $companyId = company()->id;
        $fallbackEmpHq = $targetEmpDetails ? ($targetEmpDetails->headquarter_id ?? $targetEmpDetails->pharma_headquarter_id ?? null) : null;

        if (!$station) {
            return $requestHeadquarterId ?? $fallbackEmpHq;
        }

        $hqByName = PharmaHeadquarter::where('company_id', $companyId)->where('name', $station)->first();
        if ($hqByName) {
            return (int) $hqByName->id;
        }

        $ex = PharmaExstation::where('company_id', $companyId)->where('name', $station)->first();
        if ($ex) {
            $hqs = $ex->headquarters()->get();
            if ($hqs->count() === 1) {
                return (int) $hqs->first()->id;
            }
            if ($hqs->count() > 1 && $requestHeadquarterId) {
                $picked = $hqs->firstWhere('id', $requestHeadquarterId);
                if ($picked) {
                    return (int) $picked->id;
                }
            }

            return $hqs->isNotEmpty() ? (int) $hqs->first()->id : ($requestHeadquarterId ?? $fallbackEmpHq);
        }

        $out = PharmaOutstation::where('company_id', $companyId)->where('name', $station)->first();
        if ($out) {
            $hqs = $out->headquarters()->get();
            if ($hqs->count() === 1) {
                return (int) $hqs->first()->id;
            }
            if ($hqs->count() > 1 && $requestHeadquarterId) {
                $picked = $hqs->firstWhere('id', $requestHeadquarterId);
                if ($picked) {
                    return (int) $picked->id;
                }
            }

            return $hqs->isNotEmpty() ? (int) $hqs->first()->id : ($requestHeadquarterId ?? $fallbackEmpHq);
        }

        return $requestHeadquarterId ?? $fallbackEmpHq;
    }

    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_tours');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'month' => 'required|date_format:Y-m',
            'submitted_to' => 'required|exists:users,id',
            'for_employee_id' => 'nullable|integer|exists:users,id',
        ]);

        // Admin can create tour plan for another employee; otherwise tours are for current user
        $targetUserId = user()->id;
        $targetUser = null;
        if (user()->hasRole('admin') && $request->filled('for_employee_id')) {
            $targetUser = User::where('company_id', company()->id)->whereHas('employeeDetail')->find($request->for_employee_id);
            if ($targetUser) {
                $targetUserId = (int) $targetUser->id;
            }
        }
        if (!$targetUser) {
            $targetUser = User::find($targetUserId);
        }

        // Tour plan must be submitted to Reporting Manager only (per requirement 3.2.4)
        $targetEmpDetails = $targetUser ? ($targetUser->employeeDetails ?? $targetUser->employeeDetail) : null;
        $allowedSubmittedTo = $targetEmpDetails ? ($targetEmpDetails->reporting_to ?? null) : null;
        if ($allowedSubmittedTo === null) {
            return Reply::error(__('No reporting manager assigned in HR for this employee. Assign reporting manager to submit tour plan.'));
        }
        if ((int) $request->submitted_to !== (int) $allowedSubmittedTo) {
            return Reply::error(__('Tour plan must be submitted to the Reporting Manager only.'));
        }

        // Validate headquarter is in target employee's accessible list
        $request->validate(['headquarter' => 'required|exists:pharma_headquarters,id']);
        $employeeToValidate = ($targetUser && $targetUserId != user()->id) ? $targetUser : user();
        $allowedHqIds = $this->accessibleHeadquarterIds($employeeToValidate);
        if ($allowedHqIds !== null && !in_array((int) $request->headquarter, $allowedHqIds)) {
            return Reply::error(__('You can only create tour plans for allocated headquarter(s).'));
        }

        $month = Carbon::parse($request->month);

        // Auto-lock on 25th for next month: block create when month is locked (non-admin)
        if (TourMonthLock::isLocked(company()->id, (int) $month->year, (int) $month->month) && !user()->hasRole('admin')) {
            return Reply::error(__('This month\'s tour plan is locked (auto-lock on 25th). Contact admin to unlock.'));
        }
        $daysInMonth = $month->daysInMonth;
        
        // Server-side validation: Count how many days will be submitted
        $newToursCount = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            
            // Check if tour already exists
            $exists = Tour::where('user_id', $targetUserId)
                ->where('date', $date->format('Y-m-d'))
                ->exists();
            
            if (!$exists) {
                $workStatus = $request->input("work_status_{$day}");
                $station = $request->input("station_{$day}");
                $workWith = $request->input("work_with_{$day}", []);
                
                // All three fields must be filled
                if ($workStatus && $station && !empty($workWith)) {
                    $newToursCount++;
                }
            }
        }
        
        // Count existing tours for this month
        $existingToursCount = Tour::where('user_id', $targetUserId)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->count();
        
        $totalToursAfterSubmission = $existingToursCount + $newToursCount;
        
        // Validate: Total should equal days in month
        if ($totalToursAfterSubmission < $daysInMonth) {
            return Reply::error(
                'Incomplete Tour Plan! You must fill all ' . $daysInMonth . ' days with Work Type, Station, and Work With. ' .
                'Currently: ' . $existingToursCount . ' already submitted, ' . 
                $newToursCount . ' new entries = ' . $totalToursAfterSubmission . ' total. ' .
                'Missing: ' . ($daysInMonth - $totalToursAfterSubmission) . ' days.'
            );
        }
        
        // Get "Submit To" person
        $submittedTo = $request->submitted_to;
        $firstCreatedTour = null;

        // Process each day of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            
            // Check if tour already exists for this date
            $existingTour = Tour::where('user_id', $targetUserId)
                ->where('date', $date->format('Y-m-d'))
                ->first();
            
            // Skip if tour already exists (prevents duplicates)
            if ($existingTour) {
                continue;
            }
            
            // Get data for this specific day
            $workStatus = $request->input("work_status_{$day}");
            $station = $request->input("station_{$day}"); // Single value now (can be HQ name, Ex-Station, or Out-Station)
            $workWith = $request->input("work_with_{$day}", []);
            $remark = $request->input("remark_{$day}");
            
            // Skip empty rows (no data entered) - but if any data exists, all three fields are required
            if (!$workStatus && !$station && empty($workWith) && !$remark) {
                continue;
            }
            
            // Validate mandatory fields: Work Type, Station, and Work With
            if (!$workStatus || !$station || empty($workWith)) {
                return Reply::error(
                    "Day {$day} ({$date->format('Y-m-d')}): Work Type, Station, and Work With are required fields."
                );
            }
            
            $headquarterId = $this->resolveTourHeadquarterIdFromStation(
                $station,
                (int) $request->headquarter,
                $targetEmpDetails
            );
            
            $tour = Tour::create([
                'company_id' => company()->id,
                'user_id' => $targetUserId,
                'date' => $date,
                'day' => $date->format('l'), // Monday, Tuesday, etc.
                'headquarter_id' => $headquarterId,
                'work_status' => $workStatus,
                'station' => $station, // Single station value (HQ name, Ex-Station, or Out-Station)
                'work_with' => is_array($workWith) ? implode(',', $workWith) : $workWith,
                'remark' => $remark,
                'submitted_to' => $submittedTo,
                'approved' => false,
                'status' => 'pending',
            ]);
            if (!$firstCreatedTour) {
                $firstCreatedTour = $tour;
            }
        }

        if ($firstCreatedTour && $submittedTo) {
            $submittedToUser = User::find($submittedTo);
            if ($submittedToUser) {
                $submittedToUser->notify(new TourPlanSubmitted($firstCreatedTour));
            }
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('tours.index')]);
    }

    public function edit($id)
    {
        $this->tour = Tour::with(['user', 'headquarter', 'submittedTo'])->findOrFail($id);
        $this->editPermission = user()->permission('edit_tours');
        abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));

        // After approval: tour plan becomes locked (only admin can edit)
        if ($this->tour->approved && !user()->hasRole('admin')) {
            abort_403(true, __('Tour plan is locked after approval. Only admin can edit.'));
        }

        // Month lock: block edit when that month is locked (non-admin)
        $tourDate = $this->tour->date;
        if (TourMonthLock::isLocked(company()->id, (int) $tourDate->year, (int) $tourDate->month) && !user()->hasRole('admin')) {
            abort_403(true, __('This month\'s tour plan is locked (auto-lock on 25th). Contact admin to unlock.'));
        }

        $this->userHeadquarter = user()->employeeDetails->headquarter_id 
            ?? user()->employeeDetails->pharma_headquarter_id 
            ?? null;
        // Filter headquarters by user's allocation (same as create)
        $accessibleHqIds = $this->accessibleHeadquarterIds();
        $headquartersQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id);
        if ($accessibleHqIds === null) {
            $this->headquarters = $headquartersQuery->get();
        } elseif (empty($accessibleHqIds)) {
            $this->headquarters = collect();
        } else {
            $this->headquarters = $headquartersQuery->whereIn('id', $accessibleHqIds)->get();
        }
        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        $this->employees = User::allEmployees();
        // "Submit To": only tour owner's reporting manager (same as create)
        $tourOwner = $this->tour->user;
        $tourOwnerDetails = $tourOwner ? ($tourOwner->employeeDetails ?? $tourOwner->employeeDetail) : null;
        $reportingManagerId = $tourOwnerDetails ? ($tourOwnerDetails->reporting_to ?? null) : null;
        if ($reportingManagerId) {
            $this->managers = User::with(['employeeDetail.designation'])
                ->whereHas('employeeDetail')
                ->where('id', $reportingManagerId)
                ->where('company_id', company()->id)
                ->get();
        } else {
            $this->managers = collect();
        }

        if (request()->ajax()) {
            $html = view('tours.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => 'Edit Tour']);
        }

        return view('tours.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $tour = Tour::findOrFail($id);
        $this->editPermission = user()->permission('edit_tours');
        abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));

        // After approval: tour plan becomes locked (only admin can edit)
        if ($tour->approved && !user()->hasRole('admin')) {
            return Reply::error(__('Tour plan is locked after approval. Only admin can edit.'));
        }

        // Month lock: block update when tour's month is locked (non-admin)
        $tourDate = $tour->date;
        if (TourMonthLock::isLocked(company()->id, (int) $tourDate->year, (int) $tourDate->month) && !user()->hasRole('admin')) {
            return Reply::error(__('This month\'s tour plan is locked (auto-lock on 25th). Contact admin to unlock.'));
        }

        $request->validate([
            'date' => 'required|date',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'nullable|exists:users,id',
        ]);

        // Validate headquarter is in tour owner's accessible list
        $tourOwner = User::find($tour->user_id);
        $employeeToValidate = $tourOwner ?: user();
        $allowedHqIds = $this->accessibleHeadquarterIds($employeeToValidate);
        $tourOwnerEmp = $tourOwner ? ($tourOwner->employeeDetails ?? $tourOwner->employeeDetail) : null;
        $headquarterIdToValidate = $this->resolveTourHeadquarterIdFromStation(
            $request->station,
            (int) $request->headquarter_id,
            $tourOwnerEmp
        ) ?? (int) $request->headquarter_id;
        if ($allowedHqIds !== null && !in_array((int) $headquarterIdToValidate, $allowedHqIds)) {
            return Reply::error(__('You can only assign allocated headquarter(s) to this tour.'));
        }

        // Tour plan must remain submitted to tour owner's Reporting Manager only
        $tourOwnerDetails = $tourOwner ? ($tourOwner->employeeDetails ?? $tourOwner->employeeDetail) : null;
        $allowedSubmittedTo = $tourOwnerDetails ? ($tourOwnerDetails->reporting_to ?? null) : null;
        if ($request->has('submitted_to') && $request->submitted_to !== null) {
            if ($allowedSubmittedTo === null || (int) $request->submitted_to !== (int) $allowedSubmittedTo) {
                return Reply::error(__('Tour plan must be submitted to the Reporting Manager only.'));
            }
        }

        $headquarterId = $this->resolveTourHeadquarterIdFromStation(
            $request->station,
            (int) $request->headquarter_id,
            $tourOwnerEmp
        ) ?? (int) $request->headquarter_id;

        $tour->update([
            'date' => $request->date,
            'day' => Carbon::parse($request->date)->format('l'),
            'headquarter_id' => $headquarterId,
            'work_status' => $request->work_status,
            'station' => $request->station, // Single station value (HQ name, Ex-Station, or Out-Station)
            'work_with' => is_array($request->work_with) ? implode(',', $request->work_with) : $request->work_with,
            'remark' => $request->remark,
            'submitted_to' => $allowedSubmittedTo ?? $request->submitted_to,
        ]);

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('tours.index')]);
    }

    public function approve($id)
    {
        $tour = Tour::findOrFail($id);
        
        // Check if user can approve: admin, person tour is submitted to, or tour creator reports to current user (align with DCR/Expense)
        $isAdmin = user()->permission('approve_tours') == 'all';
        $isSubmittedToMe = $tour->submitted_to == user()->id;
        $isReportingEmployee = false;
        if (!$isAdmin && !$isSubmittedToMe && $tour->user) {
            $emp = $tour->user->employeeDetail ?? $tour->user->employeeDetails ?? null;
            $isReportingEmployee = $emp && $emp->reporting_to == user()->id;
        }
        $canApprove = $isAdmin || $isSubmittedToMe || $isReportingEmployee;
        abort_403(!$canApprove);
        
        $tour->approved = true;
        $tour->approved_by = user()->id;
        $tour->approved_at = now();
        $tour->status = 'approved';
        $tour->save();

        if ($tour->user) {
            $tour->user->notify(new TourPlanApproved($tour->load('company')));
        }

        return Reply::success(__('Tour approved successfully'));
    }

    /**
     * Reject a single tour (same permission as approve: admin, submitted to, or creator reports to current user).
     */
    public function reject($id)
    {
        $tour = Tour::findOrFail($id);

        $isAdmin = user()->permission('approve_tours') == 'all';
        $isSubmittedToMe = $tour->submitted_to == user()->id;
        $isReportingEmployee = false;
        if (!$isAdmin && !$isSubmittedToMe && $tour->user) {
            $emp = $tour->user->employeeDetail ?? $tour->user->employeeDetails ?? null;
            $isReportingEmployee = $emp && $emp->reporting_to == user()->id;
        }
        $canReject = $isAdmin || $isSubmittedToMe || $isReportingEmployee;
        abort_403(!$canReject);

        $tour->approved = false;
        $tour->approved_by = null;
        $tour->approved_at = null;
        $tour->status = 'rejected';
        $tour->save();

        return Reply::success(__('Tour rejected.'));
    }
    
    public function approveAll(Request $request)
    {
        $this->approvePermission = user()->permission('approve_tours');
        $isAdmin = $this->approvePermission == 'all';
        
        $request->validate([
            'tour_ids' => 'required|array',
            'tour_ids.*' => 'required|integer|exists:tours,id'
        ]);
        
        $tourIds = $request->tour_ids;
        
        // If not admin, check that tours are submitted to current user OR from reporting employees
        if (!$isAdmin) {
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $toursAccessible = Tour::whereIn('id', $tourIds)
                ->where(function($q) use ($reportingEmployeeIds) {
                    $q->where('submitted_to', user()->id);
                    if (!empty($reportingEmployeeIds)) {
                        $q->orWhereIn('user_id', $reportingEmployeeIds);
                    }
                })
                ->count();
            abort_403($toursAccessible == 0);
        }
        
        // Approve all tours (admin can approve any, reporting manager can approve tours submitted to them or from reporting employees)
        $query = Tour::whereIn('id', $tourIds)
            ->where('company_id', company()->id)
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status');
            });
        
        if (!$isAdmin) {
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $query->where(function($q) use ($reportingEmployeeIds) {
                $q->where('submitted_to', user()->id);
                if (!empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
        }
        
        $toursToApprove = $query->with('user')->get();
        $query->update([
            'approved' => true,
            'approved_by' => user()->id,
            'approved_at' => now(),
            'status' => 'approved'
        ]);

        foreach ($toursToApprove as $t) {
            if ($t->user) {
                $t->user->notify(new TourPlanApproved($t->load('company')));
            }
        }

        return Reply::success(__('All tours approved successfully'));
    }

    /**
     * Bulk reject tours (same permission as approveAll; only pending tours are rejected).
     */
    public function rejectBulk(Request $request)
    {
        $this->approvePermission = user()->permission('approve_tours');
        $isAdmin = $this->approvePermission == 'all';

        $request->validate([
            'tour_ids' => 'required|array',
            'tour_ids.*' => 'required|integer|exists:tours,id',
        ]);

        $tourIds = $request->tour_ids;

        if (!$isAdmin) {
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $toursAccessible = Tour::whereIn('id', $tourIds)
                ->where(function($q) use ($reportingEmployeeIds) {
                    $q->where('submitted_to', user()->id);
                    if (!empty($reportingEmployeeIds)) {
                        $q->orWhereIn('user_id', $reportingEmployeeIds);
                    }
                })
                ->count();
            abort_403($toursAccessible == 0);
        }

        $query = Tour::whereIn('id', $tourIds)
            ->where('company_id', company()->id)
            ->where(function ($q) {
                $q->where('status', 'pending')->orWhereNull('status');
            });

        if (!$isAdmin) {
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $query->where(function($q) use ($reportingEmployeeIds) {
                $q->where('submitted_to', user()->id);
                if (!empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
        }

        $query->update([
            'approved' => false,
            'approved_by' => null,
            'approved_at' => null,
            'status' => 'rejected',
        ]);

        return Reply::success(__('Selected tours rejected.'));
    }

    public function destroy($id)
    {
        $tour = Tour::findOrFail($id);
        $this->deletePermission = user()->permission('delete_tours');
        abort_403(!in_array($this->deletePermission, ['all', 'added', 'owned', 'both']));

        $tour->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }
    
    public function status(Request $request)
    {
        $this->viewPermission = user()->permission('view_tours');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        // Month filter - default to current month
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $this->selectedMonth = $selectedMonth;
        
        // Parse month for query filtering
        $monthStart = \Carbon\Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $monthEnd = \Carbon\Carbon::parse($selectedMonth . '-01')->endOfMonth();
        
        // For admin: employee filter
        $selectedEmployeeId = $request->get('employee_id', 'all');
        $this->selectedEmployeeId = $selectedEmployeeId;
        
        // Get all employees for dropdown (admin only)
        if ($this->viewPermission == 'all') {
            $this->employees = User::with(['employeeDetail.designation'])
                ->whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->orderBy('name')
                ->get();
        } else {
            $this->employees = collect();
        }

        // Load tours with approval details
        // Note: work_with is now stored as comma-separated designation names, not user IDs
        $toursQuery = Tour::with([
            'user.employeeDetail.designation',
            'user.employeeDetails.designation',
            'user.employeeDetail.headquarter',
            'user.employeeDetails.headquarter',
            'headquarter', 
            'approvedBy', 
            'submittedTo.employeeDetail.designation',
            'submittedTo.employeeDetails.designation'
        ]);
        
        // Filter by month
        $toursQuery = $toursQuery->whereBetween('date', [$monthStart, $monthEnd]);
        
        // Admin can see all tours or filter by employee
        if ($this->viewPermission == 'all') {
            $toursQuery = $toursQuery->where('company_id', company()->id);
            
            // Admin can filter by specific employee
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $toursQuery = $toursQuery->where('user_id', $selectedEmployeeId);
            }
        } else {
            // Non-admin: own tours, inbox (submitted to me), or tours from direct reports (hierarchy-scoped)
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIdsInScope = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

            $toursQuery = $toursQuery->where('company_id', company()->id)
                ->where(function ($q) use ($reportingEmployeeIdsInScope) {
                    $q->where(function ($q2) {
                        $q2->where('user_id', user()->id)
                            ->orWhere('submitted_to', user()->id);
                    });
                    if (!empty($reportingEmployeeIdsInScope)) {
                        $q->orWhereIn('user_id', $reportingEmployeeIdsInScope);
                    }
                });
        }
        
        $this->tours = $toursQuery->orderBy('date', 'desc')->get();
        
        // Calculate statistics
        $this->totalTours = $this->tours->count();
        $this->approvedTours = $this->tours->where('status', 'approved')->count();
        $this->pendingTours = $this->tours->where('status', 'pending')->count() + $this->tours->whereNull('status')->count();
        $this->rejectedTours = $this->tours->where('status', 'rejected')->count();
        $this->approvalRate = $this->totalTours > 0 ? round(($this->approvedTours / $this->totalTours) * 100) : 0;
        
        return view('tours.status', $this->data);
    }
    
    public function bulkDelete(Request $request)
    {
        $this->deletePermission = user()->permission('delete_tours');
        abort_403($this->deletePermission != 'all'); // Only admins with 'all' permission
        
        $request->validate([
            'tour_ids' => 'required|array',
            'tour_ids.*' => 'required|integer|exists:tours,id'
        ]);
        
        $tourIds = $request->tour_ids;
        
        // Delete tours
        Tour::whereIn('id', $tourIds)
            ->where('company_id', company()->id) // Security: only delete from same company
            ->delete();
        
        return Reply::success(__('Tours deleted successfully'));
    }

    /**
     * Admin-only: remove month lock so that month is open again for tour create/edit.
     */
    public function unlockMonth(Request $request)
    {
        abort_403(!user()->hasRole('admin'));

        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $deleted = TourMonthLock::where('company_id', company()->id)
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::success($deleted
                ? __('Month unlocked. Tour plan can be created/edited for this month.')
                : __('No lock found for this month.'));
        }

        return redirect()->route('tours.index')
            ->with('success', $deleted
                ? __('Month unlocked. Tour plan can be created/edited for this month.')
                : __('No lock found for this month.'));
    }

}
