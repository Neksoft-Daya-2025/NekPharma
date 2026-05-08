@extends('layouts.app')

@push('styles')
<style>
    body { background-color: #f8f9fa; }
    .table thead th { background-color: #0d6efd; color: white; text-align: center; }
    .month-header {
        background: linear-gradient(135deg, #8bab4c 0%, #6a8c3a 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .tour-calendar tr.sunday-row {
        background-color: #ffebee !important;
    }
    .tour-calendar tr.sunday-row td {
        color: #c62828;
        font-weight: 600;
    }
    .table-sm td, .table-sm th {
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    
    /* Fix Select2 in table cells */
    .tour-calendar td {
        position: relative;
        vertical-align: middle;
        padding: 8px 6px !important;
    }
    
    .tour-calendar .select2-container {
        width: 100% !important;
        position: relative;
        z-index: 1;
    }
    
    .tour-calendar .select2-container .select2-selection--multiple {
        min-height: 36px !important;
        max-height: 60px !important;
        overflow-y: auto !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        padding: 2px !important;
    }
    
    .tour-calendar .select2-container .select2-selection--multiple .select2-selection__rendered {
        padding: 2px 4px !important;
        display: flex;
        flex-wrap: wrap;
        max-height: 56px;
        overflow-y: auto;
    }
    
    .tour-calendar .select2-container .select2-selection--multiple .select2-selection__choice {
        background-color: #8bab4c !important;
        border: 1px solid #7a9a3f !important;
        color: white !important;
        font-size: 10px !important;
        padding: 1px 5px !important;
        margin: 1px !important;
        line-height: 18px !important;
        height: 20px !important;
        display: inline-flex;
        align-items: center;
    }
    
    .tour-calendar .select2-container .select2-selection--multiple .select2-selection__choice__remove {
        color: white !important;
        margin-right: 3px !important;
        font-size: 14px !important;
    }
    
    .tour-calendar .select2-container .select2-search--inline {
        width: auto !important;
        min-width: 50px !important;
    }
    
    .tour-calendar .select2-container .select2-search--inline .select2-search__field {
        font-size: 11px !important;
        margin: 0 !important;
        padding: 0 4px !important;
    }
    
    /* Improve form controls in table */
    .tour-calendar .form-control-sm {
        height: 36px;
        font-size: 12px;
        padding: 6px 8px;
    }
    
    /* Prevent overlap */
    .tour-calendar tr {
        height: 50px;
    }
    
    .tour-status-approved {
        background-color: #d4edda !important;
        border-left: 4px solid #28a745 !important;
    }
    
    .tour-status-approved td {
        color: #555 !important;
        opacity: 0.8;
    }
    
    .tour-status-pending {
        background-color: #fff3cd !important;
        border-left: 4px solid #ffc107 !important;
    }
    
    .tour-submitted {
        background-color: #e9ecef !important;
        border-left: 4px solid #6c757d !important;
    }
    
    .tour-submitted input,
    .tour-submitted select,
    .tour-submitted .select2-container {
        background-color: #f8f9fa !important;
        cursor: not-allowed !important;
    }
    
    .lock-icon {
        font-size: 16px;
        margin-right: 5px;
    }
    
    .status-indicator {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-indicator.approved {
        background: #28a745;
        color: white;
    }
    
    .status-indicator.pending {
        background: #ffc107;
        color: #000;
    }
    
    .status-indicator.rejected {
        background: #dc3545;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="month-header w-100 text-center">
            <h3 class="mb-0"><i class="fa fa-check-circle"></i> Approve Tour Plan</h3>
            <p class="mb-0 mt-2">Review, edit, and approve submitted tour plans</p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <form action="{{ route('tours.store') }}" method="POST" id="tour-plan-form">
            @csrf
            
            <!-- Month, Employee, and HeadQuarter Selector -->
            <div class="row mb-4 align-items-start">
                @if(user()->hasAdminLikeAccess() || ($employees->isNotEmpty() && !user()->hasAdminLikeAccess()))
                    <div class="col-md-3">
                        <label for="employee-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                            Select Employee <sup class="f-14 mr-1 text-danger">*</sup>
                        </label>
                        <select class="form-control height-35 f-14 select-picker" name="employee_id" id="employee-selector" data-live-search="true" data-html="true">
                            @if(user()->hasAdminLikeAccess())
                                <option value="all" {{ (!$selectedEmployeeId || $selectedEmployeeId == 'all') ? 'selected' : '' }}>-- All Employees --</option>
                            @else
                                <option value="all" {{ (!$selectedEmployeeId || $selectedEmployeeId == 'all') ? 'selected' : '' }}>-- All Employees (Submitted to Me) --</option>
                            @endif
                            @foreach($employees as $emp)
                                @php
                                    $empDetail = $emp->employeeDetail ?? $emp->employeeDetails;
                                    $empHeadquarterId = $empDetail ? ($empDetail->headquarter_id ?? $empDetail->pharma_headquarter_id ?? '') : '';
                                @endphp
                                <x-user-option :user="$emp" :employeeSelect="true" :selected="(string) $selectedEmployeeId === (string) $emp->id" :dataHeadquarterId="$empHeadquarterId" />
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Filter by employee to approve their tours</small>
                    </div>
                @endif
                <div class="col-md-3">
                    <label for="month-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        @lang('Select Month') <sup class="f-14 mr-1 text-danger">*</sup>
                    </label>
                    <input type="month" class="form-control height-35 f-14" name="month" 
                           id="month-selector" value="{{ $currentMonth }}" required>
                    <small class="form-text text-muted invisible mb-0" aria-hidden="true">&nbsp;</small>
                </div>
                <!--<div class="col-md-3">-->
                <!--    <label for="headquarter-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">-->
                <!--        HeadQuarter <sup class="f-14 mr-1 text-danger">*</sup>-->
                <!--    </label>-->
                <!--    @if(user()->hasAdminLikeAccess())-->
                <!--        <select class="form-control height-35 f-14 select-picker" name="headquarter" id="headquarter-selector" data-live-search="true" required>-->
                <!--            <option value="">-- Select HeadQuarter --</option>-->
                <!--            @foreach(\App\Models\PharmaHeadquarter::orderBy('name')->get() as $hq)-->
                <!--                <option value="{{ $hq->id }}">-->
                <!--                    {{ $hq->name }}-->
                <!--                    @if($hq->area) ({{ $hq->area->name }}) @endif-->
                <!--                </option>-->
                <!--            @endforeach-->
                <!--        </select>-->
                <!--        <small class="form-text text-muted">Select HQ to view tours</small>-->
                <!--    @else-->
                <!--        @if($userHeadquarter)-->
                <!--            <input type="hidden" name="headquarter" value="{{ $userHeadquarter }}">-->
                <!--            <div class="form-control height-35 f-14 bg-light" style="display: flex; align-items: center;">-->
                <!--                <span class="badge badge-success mr-2">-->
                <!--                    <i class="fa fa-lock"></i>-->
                <!--                </span>-->
                <!--                {{ \App\Models\PharmaHeadquarter::find($userHeadquarter)->name }}-->
                <!--            </div>-->
                <!--            <small class="form-text text-muted">Your assigned headquarter</small>-->
                <!--        @else-->
                <!--            <div class="form-control height-35 f-14 bg-danger text-white">-->
                <!--                <i class="fa fa-exclamation-triangle"></i> Not Assigned-->
                <!--            </div>-->
                <!--            <small class="form-text text-danger">Contact admin to assign a headquarter</small>-->
                <!--        @endif-->
                <!--    @endif-->
                <!--</div>-->
<!--========================================================= sonu ================================================                -->
                <div class="col-md-3">
                    <label for="headquarter-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        HeadQuarter <sup class="f-14 mr-1 text-danger">*</sup>
                    </label>
                
                    @if(!$headquarters->isEmpty() && isset($tourPlanEffectiveHeadquarter) && $tourPlanEffectiveHeadquarter)
                        <input type="hidden" name="headquarter" id="headquarter-selector" value="{{ $tourPlanEffectiveHeadquarter->id }}">
                        <div class="form-control height-35 f-14 bg-light d-flex align-items-center" aria-readonly="true">
                            <span class="badge badge-secondary mr-2"><i class="fa fa-lock"></i></span>
                            <span>{{ $tourPlanEffectiveHeadquarter->name }}@if($tourPlanEffectiveHeadquarter->area) ({{ $tourPlanEffectiveHeadquarter->area->name }}) @endif</span>
                        </div>
                        <small class="form-text text-muted">Headquarter is read-only; change context via employee filter if applicable.</small>
                    @endif

                </div>
<!--================================================================================================-->
                <div class="col-md-{{ user()->hasAdminLikeAccess() ? '3' : '6' }}">
                    <span class="d-block my-3 f-14 text-dark-grey mb-12 invisible user-select-none" aria-hidden="true">&nbsp;</span>
                    <div class="d-flex flex-wrap align-items-center" style="min-height: 35px;">
                        <x-forms.link-primary :link="route('tours.create')" class="mr-2" icon="plus">
                            <i class="fa fa-calendar-plus"></i> Create New Month
                        </x-forms.link-primary>
                        @if(user()->hasAdminLikeAccess() && !empty($selectedEmployeeId) && $selectedEmployeeId != 'all')
                            @php
                                $selectedEmp = $employees->firstWhere('id', (int) $selectedEmployeeId);
                                $selectedEmpLabel = $selectedEmp ? \App\Helper\EmployeeSelectLabel::plain($selectedEmp) : '';
                            @endphp
                            @if($selectedEmpLabel)
                                <x-forms.link-primary :link="route('tours.create', ['for_employee_id' => $selectedEmployeeId])" class="mr-2" icon="user">
                                    <i class="fa fa-user-edit"></i> Create tour plan for {{ $selectedEmpLabel }}
                                </x-forms.link-primary>
                            @endif
                        @endif
                        <button type="button" class="btn btn-secondary btn-sm mr-2" id="clear-form"
                            title="Clear All" aria-label="Clear All">
                            <i class="fa fa-eraser" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted invisible mb-0" aria-hidden="true">&nbsp;</small>
                </div>
            </div>

            @if(user()->hasAdminLikeAccess() && isset($lockedMonths) && $lockedMonths->isNotEmpty())
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-body py-2">
                            <small class="text-muted d-block mb-2">
                                <i class="fa fa-info-circle"></i> Next month is auto-locked on the 25th. Admin can unlock here.
                            </small>
                            <strong class="mr-2">Locked months:</strong>
                            @foreach($lockedMonths as $lock)
                                @php
                                    $monthName = \Carbon\Carbon::createFromDate($lock->year, $lock->month, 1)->format('F Y');
                                @endphp
                                <span class="badge badge-warning mr-1 mb-1 d-inline-flex align-items-center">
                                    {{ $monthName }}
                                    <form action="{{ route('tours.unlock-month') }}" method="POST" class="d-inline ml-1 unlock-month-form">
                                        @csrf
                                        <input type="hidden" name="year" value="{{ $lock->year }}">
                                        <input type="hidden" name="month" value="{{ $lock->month }}">
                                        <button type="submit" class="btn btn-link btn-sm p-0 ml-1 text-dark" style="font-size: 12px;" title="Unlock this month">
                                            <i class="fa fa-unlock"></i> Unlock
                                        </button>
                                    </form>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Action Buttons Row -->
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    @php
                        $canApproveAll = user()->permission('approve_tours') == 'all' || 
                                        \App\Models\Tour::where('submitted_to', user()->id)
                                            ->where(function($q) {
                                                $q->where('status', 'pending')->orWhereNull('status');
                                            })
                                            ->exists();
                    @endphp
                    @if($canApproveAll)
                        <button type="button" class="btn btn-success" id="approve-all-btn">
                            <i class="fa fa-check-circle"></i> Approve All Tours
                        </button>
                        <button type="button" class="btn btn-warning ml-2" id="reject-selected-btn" style="display: none;">
                            <i class="fa fa-times-circle"></i> Reject Selected (<span id="reject-selected-count">0</span>)
                        </button>
                    @endif
                    @if(user()->permission('delete_tours') == 'all')
                        <button type="button" class="btn btn-danger ml-2" id="bulk-delete-btn" style="display: none;">
                            <i class="fa fa-trash"></i> Delete Selected (<span id="selected-count">0</span>)
                        </button>
                    @endif
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover tour-calendar mb-0" id="tour-table">
                            <thead class="bg-primary text-white">
                                <tr>
                                    @if(user()->permission('delete_tours') == 'all' || $canApproveAll)
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="select-all-tours" title="Select All">
                                        </th>
                                    @endif
                                    <th style="width: 80px;"># / Status</th>
                                    <th style="width: 120px;">Date</th>
                                    <th style="width: 100px;">Day</th>
                                    <th style="width: 130px;">Submitted By</th>
                                    <th style="width: 130px;">Submit To</th>
                                    <th style="width: 140px;">Work Type</th>
                                    <th style="width: 220px;">Station(s)</th>
                                    <th style="width: 180px;">Work With</th>
                                    <th style="width: 280px;">Remark</th>
                                </tr>
                            </thead>
                            <tbody id="calendar-body">
                                <!-- Tours will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-100 border-top-grey d-flex justify-content-start px-4 py-3">
                <x-forms.button-primary id="save-tour-plan" icon="save">
                    @lang('app.update') Tour Plan
                </x-forms.button-primary>
                <x-forms.button-cancel :link="route('tours.create')" class="ml-3">@lang('app.cancel')
                </x-forms.button-cancel>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Data from controller
const headquarters = @json($headquarters);
const workStatuses = @json($workStatuses);
const employees = @json($employeesJson ?? []);
const workedWithDesignations = @json($workedWithDesignations ?? []);
const userHeadquarter = {{ $userHeadquarter ?? 'null' }};
const existingTours = @json($tours);
const isAdmin = {{ user()->permission('view_tours') == 'all' ? 'true' : 'false' }};
// canApprove: admin OR if tour is submitted to current user (reporting manager)
const canApprove = {{ user()->permission('approve_tours') == 'all' ? 'true' : 'false' }};
const isReportingManager = {{ (user()->employeeDetail && user()->employeeDetail->reporting_to) || (user()->employeeDetails && user()->employeeDetails->reporting_to) ? 'true' : 'false' }};
const canBulkDelete = {{ user()->permission('delete_tours') == 'all' ? 'true' : 'false' }};
const currentUserId = {{ user()->id }};
const managers = @json($managers);
const selectedEmployeeId = '{{ $selectedEmployeeId ?? 'all' }}';
const selectedEmployeeHeadquarter = {{ $selectedEmployeeHeadquarter ?? 'null' }};
// Map employees to include headquarter_id for easy lookup - employees are already mapped in controller
const employeesWithHeadquarters = @json($employeesJson ?? []);

// Debug: Show raw data from backend
console.log('=== RAW DATA FROM BACKEND ===');
console.log('Is Admin:', isAdmin);
console.log('Can Approve:', canApprove);
console.log('Total tours loaded:', existingTours.length);
console.log('Current User ID:', currentUserId);
if (existingTours.length > 0) {
    console.log('First tour sample:', existingTours[0]);
    console.log('First tour user:', existingTours[0].user);
    console.log('First tour user_id:', existingTours[0].user_id);
} else {
    console.log('No tours found! Check if tours exist with submitted_to =', currentUserId);
}
console.log('Employees array length:', employees.length);
if (employees.length > 0) {
    console.log('First employee sample:', employees[0]);
}
console.log('=============================');

// Store tours data by date
let toursMap = {};

// Initialize tours map - filter by selected month
function initializeToursMap(selectedMonth) {
    toursMap = {};
    existingTours.forEach(tour => {
        // Normalize date format to YYYY-MM-DD
        let tourDate = tour.date;
        if (tourDate.includes('T')) {
            tourDate = tourDate.split('T')[0];
        } else if (tourDate.includes(' ')) {
            tourDate = tourDate.split(' ')[0];
        }
        
        // Filter by selected month
        if (!selectedMonth || tourDate.startsWith(selectedMonth)) {
            toursMap[tourDate] = tour;
        }
    });
    console.log('Tours Map for month ' + selectedMonth + ':', toursMap); // Debug
}

// Load tours for selected month
function loadToursForMonth(month) {
    // Re-initialize map with filtered tours
    initializeToursMap(month);
    
    const [year, monthNum] = month.split('-');
    const daysInMonth = new Date(year, monthNum, 0).getDate();
    const $tbody = $('#calendar-body').empty();
    
    // Resolve HQ like tours/create: use visible selector for everyone (ABM+), not only view_tours === 'all'
    let defaultHqId = null;
    if ($('#headquarter-selector').length) {
        defaultHqId = $('#headquarter-selector').val() || userHeadquarter;
    } else {
        defaultHqId = userHeadquarter;
    }
    if (!defaultHqId && headquarters.length) {
        defaultHqId = headquarters[0].id;
    }

    console.log('Loading tours for month:', month, 'Default HQ ID:', defaultHqId);
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, monthNum - 1, day);
        const dateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
        const isSunday = date.getDay() === 0;
        
        // Get existing tour for this date
        const existingTour = toursMap[dateStr];
        
        // Debug log - show tour data for days with tours
        if (existingTour) {
            console.log('Day ' + day + ' (' + dateStr + '):', {
                work_status: existingTour.work_status,
                station: existingTour.station,
                work_with: existingTour.work_with,
                remark: existingTour.remark,
                status: existingTour.status
            });
        }
        
        // Status class and styling
        let statusClass = '';
        let isDisabled = false;
        let rowClass = '';
        
        if (existingTour) {
            // Tour already submitted
            if (existingTour.status === 'approved') {
                statusClass = 'tour-status-approved';
                isDisabled = true; // Approved tours are locked
            } else {
                statusClass = 'tour-status-pending';
                // Pending tours can be edited by submitter or designated approver (person tour is submitted to)
                const isSubmittedToMe = existingTour.submitted_to && (existingTour.submitted_to.id == currentUserId || existingTour.submitted_to == currentUserId);
                const canApproveThisTour = canApprove || isSubmittedToMe; // Admin OR person tour is submitted to
                isDisabled = !canApproveThisTour && existingTour.user_id != currentUserId;
            }
            rowClass = statusClass;
        } else {
            // No tour submitted yet - normal white row
            rowClass = '';
        }
        
        // Sunday override
        if (isSunday) {
            rowClass = 'sunday-row';
        }
        
        const disabledAttr = isDisabled ? 'disabled' : '';
        
        // Work Status Dropdown
        const workStatusOptions = workStatuses.map(ws => 
            `<option value="${ws.name}" ${existingTour && existingTour.work_status === ws.name ? 'selected' : ''}>${ws.name}</option>`
        ).join('');
        
        // Get HQ for this row - use tour's HQ if exists, otherwise default HQ
        let rowHqId = defaultHqId;
        if (existingTour && existingTour.headquarter_id) {
            rowHqId = existingTour.headquarter_id;
        }
        
        const hq = rowHqId ? headquarters.find(h => h.id == parseInt(rowHqId)) : null;
        
        // Station Single-select - Get station value (can be HQ name, Ex-Station, or Out-Station)
        let selectedStation = '';
        if (existingTour && existingTour.station) {
            // Station is now a single value, not comma-separated
            selectedStation = existingTour.station.trim();
            if (day === 1) {
                console.log('Day ' + day + ' - HQ:', hq ? hq.name : 'None', 'Selected station:', selectedStation);
            }
        }
        
        let stationOptions = '';
        if (hq) {
            // Add Headquarters as first option
            const hqSelected = selectedStation === hq.name ? 'selected' : '';
            stationOptions += `<option value="${hq.name}" ${hqSelected}>${hq.name} (Headquarter)</option>`;
            
            // Add Ex-Stations
            if (hq.exstations && hq.exstations.length > 0) {
                hq.exstations.forEach(st => {
                    const selected = selectedStation === st.name ? 'selected' : '';
                    stationOptions += `<option value="${st.name}" ${selected}>${st.name} (Ex-Station)</option>`;
                });
            }
            
            // Add Out-Stations
            if (hq.outstations && hq.outstations.length > 0) {
                hq.outstations.forEach(st => {
                    const selected = selectedStation === st.name ? 'selected' : '';
                    stationOptions += `<option value="${st.name}" ${selected}>${st.name} (Out-Station)</option>`;
                });
            }
            
            if (day === 1) {
                console.log('Day 1 - HQ has', hq.exstations ? hq.exstations.length : 0, 'ex-stations and', 
                    hq.outstations ? hq.outstations.length : 0, 'out-stations');
            }
        } else {
            if (day === 1) {
                console.log('WARNING: No HQ found for row! rowHqId:', rowHqId);
            }
        }
        
        // If we have a selected station but it's not in the options (might be from different HQ),
        // add it as an option so it's visible
        if (selectedStation && stationOptions && !stationOptions.includes(`value="${selectedStation}"`)) {
            stationOptions = `<option value="${selectedStation}" selected>${selectedStation}</option>` + stationOptions;
        }
        
        // Work With Multi-select - Use designations (hierarchy names) instead of employees
        // Parse comma-separated values - could be employee IDs or designations
        let selectedWorkWith = [];
        if (existingTour && existingTour.work_with) {
            // work_with might be stored as comma-separated employee IDs or designations
            // Check if values are numeric (employee IDs) or strings (designations)
            const workWithValues = existingTour.work_with.split(',').map(s => s.trim());
            selectedWorkWith = workWithValues;
        }
        
        // Build options from designations list (same as expense form)
        const workWithOptions = workedWithDesignations.map(designation => {
            const selected = selectedWorkWith.includes(designation) ? 'selected' : '';
            return `<option value="${designation}" ${selected}>${designation}</option>`;
        }).join('');
        
        const remarkValue = existingTour ? (existingTour.remark || '') : '';
        const tourId = existingTour ? existingTour.id : '';
        
        // Submitted By: same format as EmployeeSelectLabel (employee_id - name (designation))
        let submittedByLine = '-';
        if (existingTour) {
            const empRow = employees.find(emp => emp.id == existingTour.user_id);
            if (empRow && empRow.label) {
                submittedByLine = empRow.label;
            } else if (existingTour.user) {
                const u = existingTour.user;
                const userEmpDetail = existingTour.user.employee_detail || existingTour.user.employee_details;
                const des = (userEmpDetail && userEmpDetail.designation && userEmpDetail.designation.name)
                    ? userEmpDetail.designation.name || '-'
                    : '-';
                const code = userEmpDetail && userEmpDetail.employee_id ? String(userEmpDetail.employee_id) : '';
                submittedByLine = code
                    ? (code + ' - ' + (u.name || '-') + ' (' + des + ')')
                    : ((u.name || '-') + ' (' + des + ')');
            } else if (existingTour.user_id && empRow) {
                submittedByLine = empRow.label || empRow.name || '-';
            }

            if (day === 1 && existingTour) {
                console.log('Day 1 - Tour data:', {
                    tour_id: existingTour.id,
                    user_id: existingTour.user_id,
                    user: existingTour.user,
                    submittedByLine: submittedByLine
                });
            }
        }
        const submittedToName = existingTour && existingTour.submitted_to ? (existingTour.submitted_to.name || existingTour.submitted_to) : '-';
        
        // Get employee's designated headquarter
        // Check both employee_detail and employee_details (handle different relationship names)
        let employeeHeadquarter = 'Not Assigned';
        const empDetail = existingTour && existingTour.user 
            ? (existingTour.user.employee_detail || existingTour.user.employee_details) 
            : null;
            
        if (empDetail) {
            if (empDetail.headquarter && empDetail.headquarter.name) {
                employeeHeadquarter = empDetail.headquarter.name;
            } else if (empDetail.headquarter_id) {
                // Try to find headquarter from headquarters array
                const hq = headquarters.find(h => h.id === empDetail.headquarter_id);
                employeeHeadquarter = hq ? hq.name : 'HQ ID: ' + empDetail.headquarter_id;
            }
        }
        
        // Lock icon and day number display with status
        let dayDisplay = `${day}`;
        let statusIndicator = '';
        
        if (existingTour) {
            if (existingTour.status === 'approved') {
                // Green lock for approved
                dayDisplay = `<i class="fa fa-lock lock-icon text-success" title="Approved & Locked"></i>${day}`;
                statusIndicator = '<span class="status-indicator approved" title="This tour is approved and cannot be edited">✓ APPROVED</span>';
            } else {
                // Yellow lock for pending
                dayDisplay = `<i class="fa fa-lock lock-icon text-warning" title="Submitted - Awaiting Approval"></i>${day}`;
                statusIndicator = '<span class="status-indicator pending" title="This tour is awaiting approval">⏳ PENDING</span>';
            }
        } else {
            // No lock for empty dates
            dayDisplay = `<span class="text-muted">${day}</span>`;
        }
        
        // Checkbox for bulk delete (only if tour exists and user can delete)
        const canShowCheckbox = canBulkDelete || canApprove;
        const checkboxCell = canShowCheckbox && existingTour ? `
            <td class="text-center">
                <input type="checkbox" class="tour-checkbox" value="${tourId}" data-tour-id="${tourId}">
            </td>
        ` : (canShowCheckbox ? '<td></td>' : '');
        
        $tbody.append(`
            <tr class="${rowClass}" data-tour-id="${tourId}" data-date="${dateStr}">
                ${checkboxCell}
                <td class="text-center">
                    ${dayDisplay}
                    ${statusIndicator ? '<br>' + statusIndicator : ''}
                </td>
                <td><input type="date" class="form-control form-control-sm" name="date_${day}" value="${dateStr}" readonly></td>
                <td><input type="text" class="form-control form-control-sm" name="day_${day}" value="${dayName}" readonly></td>
                <td class="text-center">
                    <small><strong class="text-primary">${submittedByLine}</strong></small>
                    ${empDetail ? 
                        '<br><small class="text-muted"><i class="fa fa-map-marker-alt"></i> ' + employeeHeadquarter + '</small>' : 
                        '<br><small class="text-danger"><i class="fa fa-exclamation-circle"></i> Not Assigned</small>'}
                </td>
                <td class="text-center"><small class="text-primary"><strong>${submittedToName}</strong></small></td>
                <td>
                    <select class="form-control form-control-sm" name="work_status_${day}" ${disabledAttr}>
                        <option value="">Select</option>
                        ${workStatusOptions}
                    </select>
                </td>
                <td>
                    <select class="form-control select-station" name="station_${day}" style="width: 100%;" ${disabledAttr}>
                        <option value="">Select station...</option>
                        ${stationOptions}
                    </select>
                </td>
                <td>
                    <select class="form-control select-picker select-workwith" name="work_with_${day}" multiple data-live-search="true" data-actions-box="true" data-select-all-text="Select All" data-deselect-all-text="Deselect All" data-selected-text-format="count > 3" data-count-selected-text="{0} selected" ${disabledAttr}>
                        ${workWithOptions}
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm" name="remark_${day}" value="${remarkValue}" placeholder="Add notes..." ${disabledAttr}></td>
            </tr>
        `);
    }
    
    // Initialize Select2 for selects with delay to ensure DOM is ready
    setTimeout(function() {
        $('.select-station').each(function() {
            const $select = $(this);
            const selectedValue = $select.val(); // Get the selected value before initializing Select2
            
            $select.select2({
                width: '100%',
                placeholder: 'Select station...',
                allowClear: true
            });
            
            // Set the selected value after Select2 initialization
            if (selectedValue) {
                $select.val(selectedValue).trigger('change');
            }
        });
        
        // Initialize selectpicker for "Work With" dropdown (same as expense form)
        $('.select-workwith').each(function() {
            const $select = $(this);
            // Destroy select2 if it exists
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            // Initialize selectpicker with actionsBox (Select All / Deselect All buttons)
            $select.selectpicker({
                actionsBox: true,
                selectAllText: 'Select All',
                deselectAllText: 'Deselect All',
                multipleSeparator: ', ',
                selectedTextFormat: 'count > 3',
                countSelectedText: function(selected, total) {
                    return selected + ' of ' + total + ' selected';
                },
                liveSearch: true
            });
        });
        
        // Debug: Log first row to verify selections
        console.log('First station select:', $('.select-station').first().val());
        console.log('First workwith select:', $('.select-workwith').first().val());
        updateBulkDeleteButton();
        updateRejectSelectedButton();
    }, 100);
}

// Employee selector change (Admin only) - update headquarter and reload page with filter
$('#employee-selector').on('change', function() {
    const employeeId = $(this).val();
    const selectedOption = $(this).find('option:selected');
    const employeeHeadquarterId = selectedOption.data('headquarter-id');
    
    // Update headquarter control if employee has a headquarter (dropdown only; read-only uses page reload)
    if (employeeId && employeeId !== 'all' && employeeHeadquarterId) {
        const $hqSel = $('#headquarter-selector');
        if ($hqSel.is('select')) {
            $hqSel.val(employeeHeadquarterId);
            if ($hqSel.data('selectpicker')) {
                $hqSel.selectpicker('refresh');
            }
            console.log('Updated headquarter to employee HQ:', employeeHeadquarterId);
        }
    }
    
    const currentUrl = new URL(window.location.href);
    
    if (employeeId && employeeId !== 'all') {
        currentUrl.searchParams.set('employee_id', employeeId);
    } else {
        currentUrl.searchParams.delete('employee_id');
    }
    
    window.location.href = currentUrl.toString();
});

// On month change - auto reload
$('#month-selector').on('change', function() {
    loadToursForMonth($(this).val());
});

// For admin: HQ selector change
$('#headquarter-selector').on('change', function() {
    const hqId = $(this).val();
    if (hqId) {
        // Reload stations for selected HQ
        const hq = headquarters.find(h => h.id == hqId);
        if (hq) {
            loadToursForMonth($('#month-selector').val());
        }
    }
});

// Clear form
$('#clear-form').on('click', function() {
    if (confirm('Are you sure you want to clear all fields?')) {
        $('#tour-plan-form')[0].reset();
        loadToursForMonth($('#month-selector').val());
    }
});

// Select all tours checkbox
$('#select-all-tours').on('change', function() {
    const isChecked = $(this).is(':checked');
    $('.tour-checkbox').prop('checked', isChecked);
    updateBulkDeleteButton();
    updateRejectSelectedButton();
});

// Individual tour checkbox
$('body').on('change', '.tour-checkbox', function() {
    updateBulkDeleteButton();
    updateRejectSelectedButton();
    
    // Update select all checkbox
    const totalCheckboxes = $('.tour-checkbox').length;
    const checkedCheckboxes = $('.tour-checkbox:checked').length;
    $('#select-all-tours').prop('checked', totalCheckboxes === checkedCheckboxes);
});

// Update bulk delete button visibility and count
function updateBulkDeleteButton() {
    const checkedCount = $('.tour-checkbox:checked').length;
    $('#selected-count').text(checkedCount);
    
    if (checkedCount > 0) {
        $('#bulk-delete-btn').show();
    } else {
        $('#bulk-delete-btn').hide();
    }
}

// Update Reject Selected button visibility and count
function updateRejectSelectedButton() {
    if ($('#reject-selected-btn').length === 0) return;
    const checkedCount = $('.tour-checkbox:checked').length;
    $('#reject-selected-count').text(checkedCount);
    if (checkedCount > 0) {
        $('#reject-selected-btn').show();
    } else {
        $('#reject-selected-btn').hide();
    }
}

// Bulk delete button
$('#bulk-delete-btn').on('click', function() {
    const selectedIds = [];
    $('.tour-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        Swal.fire('Error', 'Please select tours to delete', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Delete Selected Tours',
        text: `Are you sure you want to delete ${selectedIds.length} tour(s)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.easyAjax({
                url: "{{ route('tours.bulk-delete') }}",
                type: "POST",
                data: {
                    '_token': '{{ csrf_token() }}',
                    'tour_ids': selectedIds
                },
                success: function(response) {
                    Swal.fire('Deleted!', `${selectedIds.length} tour(s) deleted successfully`, 'success');
                    window.location.reload();
                }
            });
        }
    });
});

// Reject Selected button (only pending tours are rejected on backend)
$('#reject-selected-btn').on('click', function() {
    const selectedIds = [];
    $('.tour-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        Swal.fire('Error', 'Please select tours to reject', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Reject Selected Tours',
        text: `Are you sure you want to reject ${selectedIds.length} tour(s)? Only pending tours will be rejected.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.easyAjax({
                url: "{{ route('tours.reject-bulk') }}",
                type: "POST",
                data: {
                    '_token': '{{ csrf_token() }}',
                    'tour_ids': selectedIds
                },
                success: function(response) {
                    Swal.fire('Rejected!', 'Selected tour(s) rejected successfully', 'success');
                    window.location.reload();
                }
            });
        }
    });
});

// Approve All Tours button
$('#approve-all-btn').on('click', function() {
    // Get all pending tour IDs from the current month
    const pendingTourIds = [];
    $('tr[data-tour-id]').each(function() {
        const tourId = $(this).data('tour-id');
        if (tourId) {
            pendingTourIds.push(tourId);
        }
    });
    
    if (pendingTourIds.length === 0) {
        Swal.fire('No Tours', 'No tours found to approve for this month', 'info');
        return;
    }
    
    Swal.fire({
        title: 'Approve All Tours',
        text: `Are you sure you want to approve all ${pendingTourIds.length} tour(s) for this month?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Approve All',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.easyAjax({
                url: "{{ route('tours.approve-all') }}",
                type: "POST",
                data: {
                    '_token': '{{ csrf_token() }}',
                    'tour_ids': pendingTourIds
                },
                success: function(response) {
                    Swal.fire('Approved!', `${pendingTourIds.length} tour(s) approved successfully`, 'success');
                    window.location.reload();
                }
            });
        }
    });
});

// Save/Update tour plan - Update individual tours
$('#save-tour-plan').on('click', function(e) {
    e.preventDefault();
    
    // Collect all tour updates
    const tourUpdates = [];
    const month = $('#month-selector').val();
    const [year, monthNum] = month.split('-');
    const daysInMonth = new Date(year, monthNum, 0).getDate();
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, monthNum - 1, day);
        const dateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const existingTour = toursMap[dateStr];
        
        if (!existingTour || !existingTour.id) {
            continue; // Skip days without tours
        }
        
        const workStatus = $(`select[name="work_status_${day}"]`).val();
        const station = $(`select[name="station_${day}"]`).val();
        // work_with is a selectpicker multi-select, get selected values
        const workWithSelect = $(`select[name="work_with_${day}"]`);
        const workWith = workWithSelect.selectpicker ? workWithSelect.selectpicker('val') : workWithSelect.val();
        const remark = $(`input[name="remark_${day}"]`).val();
        
        // Get headquarter_id from the tour's headquarter or form
        const headquarterId = existingTour.headquarter_id || $('#headquarter-selector').val();
        
        tourUpdates.push({
            id: existingTour.id,
            date: dateStr,
            headquarter_id: headquarterId,
            work_status: workStatus,
            station: station,
            work_with: Array.isArray(workWith) ? workWith.join(',') : (workWith || ''),
            remark: remark || ''
        });
    }
    
    if (tourUpdates.length === 0) {
        Swal.fire('No Tours', 'No tours found to update', 'info');
        return;
    }
    
    // Disable the button to prevent multiple clicks
    const $saveBtn = $('#save-tour-plan');
    $saveBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
    
    // Update each tour individually
    let updateCount = 0;
    let errorCount = 0;
    const totalUpdates = tourUpdates.length;
    let hasShownMessage = false; // Prevent multiple success messages
    
    tourUpdates.forEach(function(tourData, index) {
        setTimeout(function() {
            $.easyAjax({
                url: "{{ route('tours.update', ':id') }}".replace(':id', tourData.id),
                type: "POST",
                data: {
                    '_token': '{{ csrf_token() }}',
                    '_method': 'PUT',
                    'date': tourData.date,
                    'headquarter_id': tourData.headquarter_id,
                    'work_status': tourData.work_status,
                    'station': tourData.station,
                    'work_with': tourData.work_with,
                    'remark': tourData.remark
                },
                success: function(response) {
                    updateCount++;
                    // Only show message and reload when ALL updates are complete
                    if (updateCount + errorCount === totalUpdates && !hasShownMessage) {
                        hasShownMessage = true;
                        $saveBtn.prop('disabled', false).html('<i class="fa fa-save"></i> @lang("app.update") Tour Plan');
                        
                        if (errorCount === 0) {
                            $.showToastr(`${updateCount} tour(s) updated successfully`, 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            $.showToastr(`${updateCount} tour(s) updated, ${errorCount} failed`, 'warning');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }
                    }
                },
                error: function(response) {
                    errorCount++;
                    console.error('Error updating tour:', tourData.id, response);
                    // Only show message and reload when ALL updates are complete
                    if (updateCount + errorCount === totalUpdates && !hasShownMessage) {
                        hasShownMessage = true;
                        $saveBtn.prop('disabled', false).html('<i class="fa fa-save"></i> @lang("app.update") Tour Plan');
                        
                        if (updateCount > 0) {
                            $.showToastr(`${updateCount} tour(s) updated, ${errorCount} failed`, 'error');
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            $.showToastr('Failed to update tours', 'error');
                        }
                    }
                }
            });
        }, index * 50); // Stagger requests to avoid overwhelming server
    });
});

// Initialize on page load - AUTO LOAD TOURS
$(document).ready(function() {
    // Initialize select picker
    if ($('.select-picker').length) {
        $('.select-picker').selectpicker();
    }
    
    const currentMonth = $('#month-selector').val();
    console.log('Initial month:', currentMonth);
    console.log('User HQ:', userHeadquarter);
    console.log('Is Admin:', isAdmin);
    console.log('All Headquarters:', headquarters);
    console.log('Selected Employee ID:', selectedEmployeeId);
    console.log('Selected Employee Headquarter:', selectedEmployeeHeadquarter);
    console.log('Employees with Headquarters:', employeesWithHeadquarters);
    
    // Headquarter is read-only (server-rendered hidden input); sync calendar filter
    const $hqControl = $('#headquarter-selector');
    if ($hqControl.length && !$hqControl.is('select')) {
        $hqControl.trigger('change');
    }

    // Auto-load tours immediately
    loadToursForMonth(currentMonth);
});
</script>
@endpush
