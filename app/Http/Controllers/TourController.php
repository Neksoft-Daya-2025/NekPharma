<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\Tour;
use App\Models\PharmaHeadquarter;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TourController extends AccountBaseController
{
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
        
        // Load headquarters with all their stations
        $this->headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])->get();
        
        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        $this->employees = User::allEmployees();
        
        // Get managers/supervisors for "Submit To" dropdown
        $this->managers = User::allEmployees()->where('id', '!=', user()->id);
        
        $this->currentMonth = now()->format('Y-m');
        
        // For admin: employee filter
        $selectedEmployeeId = $request->get('employee_id');
        $this->selectedEmployeeId = $selectedEmployeeId;
        
        // Load tours - include all relevant data
        $toursQuery = Tour::with(['user', 'headquarter', 'workingWith', 'approvedBy', 'submittedTo']);
        
        // APPROVAL PAGE: Show only tours submitted TO the current user for approval
        // This page is for managers/admins to approve tours submitted to them
        if ($this->viewPermission == 'all') {
            // Admin can see all tours OR filter by specific employee
            $toursQuery = $toursQuery->where('company_id', company()->id);
            
            // Admin can filter by specific employee
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $toursQuery = $toursQuery->where('user_id', $selectedEmployeeId);
            }
        } else {
            // Non-admin: Show ONLY tours submitted TO this user for approval
            // Do NOT show tours they created themselves
            $toursQuery = $toursQuery->where('submitted_to', user()->id);
        }
        
        $this->tours = $toursQuery->orderBy('date', 'asc')->get();
        
        // Check if user has any tours submitted to them (to show/hide menu item)
        $this->hasToursToApprove = Tour::where('submitted_to', user()->id)
            ->where('company_id', company()->id)
            ->exists();

        return view('tours.index', $this->data);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_tours');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->userHeadquarter = user()->employeeDetails->headquarter_id 
            ?? user()->employeeDetails->pharma_headquarter_id 
            ?? null;
        
        // Load all headquarters with their stations for dropdown/auto-population
        $this->headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])->get();
        
        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        
        // Get ALL employees with their headquarter and designation
        $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter'])
            ->whereHas('employeeDetail')
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
        
        // Get employee's reporting manager from HR
        $this->reportingManagerId = optional(user()->employeeDetails)->reporting_to;
        
        // Get managers/supervisors for "Submit To" dropdown - show hierarchy
        // Load with employee details to show designation
        $this->managers = User::with(['employeeDetail.designation'])
            ->whereHas('employeeDetail')
            ->where('id', '!=', user()->id)
            ->where('company_id', company()->id)
            ->orderBy('name')
            ->get();
        
        $this->currentMonth = now()->format('Y-m');
        
        // Load existing tours for current user to show which dates are already submitted
        $this->existingTours = Tour::with(['user', 'headquarter', 'workingWith', 'approvedBy', 'submittedTo'])
            ->where('user_id', user()->id)
            ->orderBy('date', 'asc')
            ->get();

        if (request()->ajax()) {
            $html = view('tours.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('tours.create', $this->data);
    }
    
    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_tours');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'month' => 'required|date_format:Y-m',
            'submitted_to' => 'required|exists:users,id',
        ]);

        $month = Carbon::parse($request->month);
        $daysInMonth = $month->daysInMonth;
        
        // Server-side validation: Count how many days will be submitted
        $newToursCount = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            
            // Check if tour already exists
            $exists = Tour::where('user_id', user()->id)
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
        $existingToursCount = Tour::where('user_id', user()->id)
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

        // Process each day of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            
            // Check if tour already exists for this date
            $existingTour = Tour::where('user_id', user()->id)
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
            
            // Determine headquarter_id based on station value
            // If station is a HQ name, find that HQ
            // Otherwise, use the top-level HQ selector (for Ex/Out stations)
            $headquarterId = null;
            if ($station) {
                // Try to find HQ by name (station might be a headquarters name)
                $hqByName = \App\Models\PharmaHeadquarter::where('name', $station)->first();
                if ($hqByName) {
                    $headquarterId = $hqByName->id;
                } else {
                    // Station is Ex-Station or Out-Station, use top-level HQ selector
                    $headquarterId = $request->headquarter 
                        ?? user()->employeeDetails->headquarter_id 
                        ?? user()->employeeDetails->pharma_headquarter_id;
                }
            } else {
                // No station selected, use top-level HQ selector
                $headquarterId = $request->headquarter 
                    ?? user()->employeeDetails->headquarter_id 
                    ?? user()->employeeDetails->pharma_headquarter_id;
            }
            
            Tour::create([
                'company_id' => company()->id,
                'user_id' => user()->id,
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
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('tours.index')]);
    }

    public function edit($id)
    {
        $this->tour = Tour::with(['user', 'headquarter', 'submittedTo'])->findOrFail($id);
        $this->editPermission = user()->permission('edit_tours');
        abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));

        $this->userHeadquarter = user()->employeeDetails->headquarter_id 
            ?? user()->employeeDetails->pharma_headquarter_id 
            ?? null;
        $this->headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])->get();
        $this->workStatuses = \App\Models\TourWorkStatus::where('is_active', true)->get();
        $this->employees = User::allEmployees();
        $this->managers = User::allEmployees()->where('id', '!=', user()->id);

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

        $request->validate([
            'date' => 'required|date',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'submitted_to' => 'nullable|exists:users,id',
        ]);

        // Determine headquarter_id based on station value if station is a HQ name
        $headquarterId = $request->headquarter_id;
        if ($request->station) {
            // Try to find HQ by name (station might be a headquarters name)
            $hqByName = \App\Models\PharmaHeadquarter::where('name', $request->station)->first();
            if ($hqByName) {
                $headquarterId = $hqByName->id;
            }
        }

        $tour->update([
            'date' => $request->date,
            'day' => Carbon::parse($request->date)->format('l'),
            'headquarter_id' => $headquarterId,
            'work_status' => $request->work_status,
            'station' => $request->station, // Single station value (HQ name, Ex-Station, or Out-Station)
            'work_with' => is_array($request->work_with) ? implode(',', $request->work_with) : $request->work_with,
            'remark' => $request->remark,
            'submitted_to' => $request->submitted_to,
        ]);

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('tours.index')]);
    }

    public function approve($id)
    {
        $tour = Tour::findOrFail($id);
        
        // Check if user can approve: either admin or the person tour is submitted to
        $canApprove = user()->permission('approve_tours') == 'all' || $tour->submitted_to == user()->id;
        abort_403(!$canApprove);
        
        $tour->approved = true;
        $tour->approved_by = user()->id;
        $tour->approved_at = now();
        $tour->status = 'approved';
        $tour->save();

        return Reply::success(__('Tour approved successfully'));
    }
    
    public function approveAll(Request $request)
    {
        $this->approvePermission = user()->permission('approve_tours');
        abort_403($this->approvePermission != 'all'); // Only admins or designated managers
        
        $request->validate([
            'tour_ids' => 'required|array',
            'tour_ids.*' => 'required|integer|exists:tours,id'
        ]);
        
        $tourIds = $request->tour_ids;
        
        // Approve all tours
        Tour::whereIn('id', $tourIds)
            ->where('company_id', company()->id)
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhereNull('status');
            })
            ->update([
                'approved' => true,
                'approved_by' => user()->id,
                'approved_at' => now(),
                'status' => 'approved'
            ]);
        
        return Reply::success(__('All tours approved successfully'));
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
        $toursQuery = Tour::with(['user', 'headquarter', 'workingWith', 'approvedBy', 'submittedTo']);
        
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
            // Non-admin: Show only tours created by current user
            $toursQuery = $toursQuery->where('user_id', user()->id);
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
}
