@extends('layouts.app')

@push('styles')
<style>
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
    .month-header {
        background: linear-gradient(135deg, #8bab4c 0%, #6a8c3a 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
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
    
    /* Status-based row styling */
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
    
    /* Status Cards */
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .text-xs {
        font-size: 0.75rem;
    }
    .text-gray-800 {
        color: #5a5c69 !important;
    }
    .shadow {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="month-header w-100 text-center">
            <h3 class="mb-0"><i class="fa fa-calendar-plus"></i> Create Tour Plan</h3>
            <p class="mb-0 mt-2">Plan your entire month's field visits and submit for approval</p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <form action="{{ route('tours.store') }}" method="POST" id="tour-plan-form">
            @csrf
            
            <!-- Month and HeadQuarter Selector -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <x-forms.label class="my-3" fieldId="month" :fieldLabel="__('Select Month')" fieldRequired="true" />
                    <input type="month" class="form-control height-35 f-14" name="month" 
                           id="month-selector" value="{{ $currentMonth }}" required>
                    <small class="form-text text-muted">Select any month</small>
                </div>
                <div class="col-md-3">
                    <x-forms.label class="my-3" fieldId="headquarter" :fieldLabel="'HeadQuarter'" fieldRequired="true" />
                    @if(user()->hasRole('admin'))
                        <select class="form-control height-35 f-14 select-picker" name="headquarter" id="headquarter-selector" data-live-search="true" required>
                            <option value="">-- Select HeadQuarter --</option>
                            @foreach($headquarters as $hq)
                                <option value="{{ $hq->id }}">
                                    {{ $hq->name }}
                                    @if($hq->area) ({{ $hq->area->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Select HQ to plan tours for</small>
                    @else
                        @if($userHeadquarter)
                            <input type="hidden" name="headquarter" value="{{ $userHeadquarter }}">
                            <div class="form-control height-35 f-14 bg-light" style="display: flex; align-items: center;">
                                <span class="badge badge-success mr-2">
                                    <i class="fa fa-lock"></i>
                                </span>
                                {{ \App\Models\PharmaHeadquarter::find($userHeadquarter)->name }}
                            </div>
                            <small class="form-text text-muted">Your assigned headquarter</small>
                        @else
                            <div class="form-control height-35 f-14 bg-danger text-white">
                                <i class="fa fa-exclamation-triangle"></i> Not Assigned
                            </div>
                            <small class="form-text text-danger">Contact admin to assign a headquarter</small>
                        @endif
                    @endif
                </div>
                <div class="col-md-3">
                    <label for="submitted-to-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        Submit To (Manager) <sup class="f-14 mr-1 text-danger">*</sup>
                    </label>
                    <select class="form-control height-35 f-14 select-picker" name="submitted_to" id="submitted-to-selector" data-live-search="true" required>
                        <option value="">-- Select Manager --</option>
                        @if($reportingManagerId)
                            @php
                                $reportingManager = $managers->firstWhere('id', $reportingManagerId);
                            @endphp
                            @if($reportingManager)
                                <option value="{{ $reportingManager->id }}" selected style="background-color: #e7f3ff; font-weight: bold;">
                                    ⭐ {{ $reportingManager->name }} (Your Reporting Manager)
                                    @if($reportingManager->employeeDetail && $reportingManager->employeeDetail->designation)
                                        - {{ $reportingManager->employeeDetail->designation->name }}
                                    @endif
                                </option>
                            @endif
                        @endif
                        @foreach($managers as $manager)
                            @if($manager->id != $reportingManagerId)
                                <option value="{{ $manager->id }}">
                                    {{ $manager->name }}
                                    @if($manager->employeeDetail && $manager->employeeDetail->designation)
                                        ({{ $manager->employeeDetail->designation->name }})
                                    @endif
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        @if($reportingManagerId)
                            <span class="text-success"><i class="fa fa-check-circle"></i> Your reporting manager is pre-selected</span>
                        @else
                            <span class="text-warning"><i class="fa fa-exclamation-triangle"></i> No reporting manager assigned in HR</span>
                        @endif
                    </small>
                </div>
                <div class="col-md-3 d-flex align-items-end pb-3">
                    <button type="button" class="btn btn-success btn-sm mr-2" id="quick-fill-all">
                        <i class="fa fa-magic"></i> Quick Fill All Days
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="clear-form">
                        <i class="fa fa-eraser"></i> Clear All
                    </button>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="row mb-3" id="status-cards" style="display: none;">
                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="approved-count">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="pending-count">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Empty (To Fill)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="empty-count">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa fa-calendar-plus fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Days</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-days">0</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fa fa-calendar fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover tour-calendar mb-0" id="tour-table">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th style="width: 120px;">Date</th>
                                    <th style="width: 100px;">Day</th>
                                    <th style="width: 140px;">Work Type</th>
                                    <th style="width: 220px;">Station</th>
                                    <th style="width: 180px;">Work With</th>
                                    <th style="width: 280px;">Remark</th>
                                    <th style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="calendar-body">
                                <!-- Generated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-100 border-top-grey d-flex justify-content-start px-4 py-3">
                <x-forms.button-primary id="save-tour-plan" icon="save">
                    @lang('app.save') Tour Plan
                </x-forms.button-primary>
                <x-forms.button-cancel :link="route('tours.index')" class="ml-3">@lang('app.cancel')</x-forms.button-cancel>
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
const employees = @json($employees);
const userHeadquarter = {{ $userHeadquarter ?? 'null' }};
const reportingManagerId = {{ $reportingManagerId ?? 'null' }};
const isAdmin = {{ user()->hasRole('admin') ? 'true' : 'false' }};
const isEmployeeHQLocked = !isAdmin && headquarters.length === 1;
const existingTours = @json($existingTours);
const currentMonth = '{{ $currentMonth }}';

// Debug info
console.log('=== TOUR PLAN INIT ===');
console.log('isAdmin:', isAdmin);
console.log('userHeadquarter:', userHeadquarter);
console.log('reportingManagerId:', reportingManagerId);
console.log('headquarters count:', headquarters.length);
console.log('employees count:', employees.length);
console.log('======================');

// Store existing tours by date
let existingToursMap = {};

function initializeExistingToursMap() {
    existingToursMap = {};
    existingTours.forEach(tour => {
        let tourDate = tour.date;
        if (tourDate.includes('T')) {
            tourDate = tourDate.split('T')[0];
        } else if (tourDate.includes(' ')) {
            tourDate = tourDate.split(' ')[0];
        }
        existingToursMap[tourDate] = tour;
    });
    console.log('Existing tours already submitted:', existingToursMap);
}

// Initialize existing tours on load
initializeExistingToursMap();

// Generate calendar when month changes
$('#month-selector').on('change', function() {
    const selectedMonth = $(this).val();
    
    generateMonthlyCalendar(selectedMonth);
    
    // After calendar is generated, populate stations if HQ is selected
    setTimeout(function() {
        if (isAdmin) {
            const hqId = $('#headquarter-selector').val();
            console.log('Auto-check admin HQ after calendar gen:', hqId);
            if (hqId) {
                const hq = headquarters.find(h => h.id == parseInt(hqId));
                console.log('Found HQ for auto-populate:', hq);
                if (hq) {
                    populateStationsForAllDays(hq);
                }
            }
        } else if (userHeadquarter) {
            const empHq = headquarters.find(h => h.id == userHeadquarter);
            console.log('Auto-check employee HQ after calendar gen:', empHq);
            if (empHq) {
                populateStationsForAllDays(empHq);
            }
        }
    }, 300);
});

// For admin: Listen to HQ selection to populate stations
$('#headquarter-selector').on('change', function() {
    const hqId = $(this).val();
    console.log('HQ selector changed, ID:', hqId);
    if (hqId) {
        const hq = headquarters.find(h => h.id == parseInt(hqId));
        console.log('Found HQ object:', hq);
        if (hq) {
            console.log('Populating stations for:', hq.name);
            populateStationsForAllDays(hq);
            
            Swal.fire({
                icon: 'success',
                title: 'HeadQuarter Selected',
                html: `<small>Stations loaded for <strong>${hq.name}</strong>.<br>You can now select stations for each day.</small>`,
                timer: 2500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        } else {
            console.error('HQ not found in headquarters array');
        }
    } else {
        // Clear all station dropdowns
        clearAllStations();
    }
});

// Function to clear all station dropdowns
function clearAllStations() {
    $('select[name^="station_"]').each(function() {
        $(this).empty().append('<option value="">Select station...</option>');
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }
    });
    $('.station-select').select2({
        width: '100%',
        placeholder: 'Select station...',
        allowClear: true,
    });
}

// Function to filter employees by headquarter
function getEmployeesByHeadquarter(headquarterId) {
    return employees.filter(emp => emp.headquarter_id == headquarterId);
}

// Function to populate employees dropdown based on station selection
function populateEmployeesForDay(day, headquarterId, stationName) {
    const $employeeSelect = $(`select[name="work_with_${day}[]"]`);
    
    if ($employeeSelect.data('select2')) {
        $employeeSelect.select2('destroy');
    }
    
    $employeeSelect.empty();
    
    if (!headquarterId) {
        $employeeSelect.select2({
            width: '100%',
            placeholder: 'Select HQ first...',
        });
        return;
    }
    
    // Get all employees from the selected headquarter
    const filteredEmployees = getEmployeesByHeadquarter(headquarterId);
    
    // Populate dropdown with filtered employees
    filteredEmployees.forEach(emp => {
        const designation = emp.designation ? ` (${emp.designation})` : '';
        $employeeSelect.append(`<option value="${emp.id}">${emp.name}${designation}</option>`);
    });
    
    $employeeSelect.select2({
        width: '100%',
        placeholder: 'Select colleague(s)',
        allowClear: true,
    });
}

// Function to populate stations for all day rows (includes HQ, Ex-Stations, Out-Stations)
function populateStationsForAllDays(hq) {
    $('select[name^="station_"]').each(function() {
        const $stationSelect = $(this);
        const day = $stationSelect.data('day');
        
        // Destroy existing Select2 instance
        if ($stationSelect.data('select2')) {
            $stationSelect.select2('destroy');
        }
        
        // Clear and add new options
        $stationSelect.empty().append('<option value="">Select station...</option>');
        
        // Add Headquarters as first option
        $stationSelect.append(`<option value="${hq.name}">${hq.name} (Headquarter)</option>`);
        
        // Add exstations
        if (hq.exstations && hq.exstations.length > 0) {
            hq.exstations.forEach(station => {
                $stationSelect.append(`<option value="${station.name}">${station.name} (Ex-Station)</option>`);
            });
        }
        
        // Add outstations
        if (hq.outstations && hq.outstations.length > 0) {
            hq.outstations.forEach(station => {
                $stationSelect.append(`<option value="${station.name}">${station.name} (Out-Station)</option>`);
            });
        }
        
        // Re-initialize Select2 with new options
        $stationSelect.select2({
            width: '100%',
            placeholder: 'Select station...',
            allowClear: true,
        });
        
        // Initial population of employees for this day
        const currentDay = $stationSelect.data('day');
        if (currentDay) {
            populateEmployeesForDay(currentDay, hq.id, null);
        }
    });
}

// Global event handler for station changes (using Select2 select event)
$(document).on('select2:select', 'select[name^="station_"]', function(e) {
    const selectedStation = e.params.data.id;
    const dayNum = $(this).data('day');
    
    // Get the HQ ID - either from admin selector or employee's assigned HQ
    const hqId = isAdmin ? $('#headquarter-selector').val() : userHeadquarter;
    
    console.log(`Day ${dayNum} - Station selected: ${selectedStation}, HQ: ${hqId}`);
    
    if (hqId && selectedStation) {
        populateEmployeesForDay(dayNum, parseInt(hqId), selectedStation);
    }
});

// Also listen to regular change event as fallback
$(document).on('change', 'select[name^="station_"]', function() {
    const selectedStation = $(this).val();
    const dayNum = $(this).data('day');
    
    // Get the HQ ID - either from admin selector or employee's assigned HQ
    const hqId = isAdmin ? $('#headquarter-selector').val() : userHeadquarter;
    
    if (hqId && selectedStation) {
        console.log(`Day ${dayNum} - Station changed (fallback): ${selectedStation}`);
        populateEmployeesForDay(dayNum, parseInt(hqId), selectedStation);
    }
});

function generateMonthlyCalendar(monthValue) {
    if (!monthValue) return;
    
    // Re-initialize existing tours map for selected month
    initializeExistingToursMap();
    
    const [year, month] = monthValue.split('-');
    const daysInMonth = new Date(year, month, 0).getDate();
    const $tbody = $('#calendar-body').empty();
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month - 1, day);
        const dateStr = formatDate(date);
        const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });
        const isSunday = date.getDay() === 0;
        
        // Check if tour already exists for this date
        const existingTour = existingToursMap[dateStr];
        
        let rowClass = isSunday ? 'sunday-row' : '';
        let dayDisplay = `${day}`;
        let isDisabled = false;
        
        if (existingTour) {
            if (existingTour.status === 'approved') {
                rowClass = 'tour-status-approved';
                dayDisplay = `<i class="fa fa-lock lock-icon text-success" title="Approved & Locked"></i>${day}`;
                isDisabled = true;
            } else {
                rowClass = 'tour-status-pending';
                dayDisplay = `<i class="fa fa-lock lock-icon text-warning" title="Submitted - Pending Approval"></i>${day}`;
                isDisabled = true; // Can't re-submit, must edit in Approve page
            }
        }
        
        const disabledAttr = isDisabled ? 'disabled' : '';
        const readonlyAttr = isDisabled ? 'readonly' : '';
        
        let row = `
            <tr class="${rowClass}" data-day="${day}" data-date="${dateStr}">
                <td class="text-center"><strong>${dayDisplay}</strong></td>
                <td>${dateStr}</td>
                <td><strong>${dayName}</strong></td>
                
                <td>
                    <select class="form-control form-control-sm" name="work_status_${day}" ${disabledAttr} ${!isDisabled ? 'required' : ''}>
                        <option value="">Select</option>
                        ${workStatuses.map(ws => 
                            `<option value="${ws.name}" style="color: ${ws.color}">${ws.name}</option>`
                        ).join('')}
                    </select>
                </td>
                
                <td>
                    <select class="form-control form-control-sm station-select" name="station_${day}" data-day="${day}" ${disabledAttr} ${!isDisabled ? 'required' : ''}>
                        <option value="">Select station...</option>
                    </select>
                </td>
                
                <td>
                    <select class="form-control form-control-sm employee-select" name="work_with_${day}[]" data-day="${day}" multiple ${disabledAttr} ${!isDisabled ? 'required' : ''}>
                        <!-- Will be populated based on selected station -->
                    </select>
                </td>
                
                <td>
                    <input type="text" class="form-control form-control-sm" name="remark_${day}" 
                           placeholder="${isDisabled ? 'Already submitted' : 'Add notes...'}" ${readonlyAttr}>
                </td>
                
                <td class="text-center">
                    ${!isDisabled && day > 1 ? `<button type="button" class="btn btn-sm btn-outline-secondary copy-prev-day" data-day="${day}" title="Copy from previous day">
                        <i class="fa fa-copy"></i>
                    </button>` : (isDisabled ? '<i class="fa fa-ban text-muted" title="Cannot modify submitted tour"></i>' : '')}
                </td>
            </tr>
        `;
        
        $tbody.append(row);
    }
    
    // Initialize Select2 for all multi-selects
    initializeSelect2();
    
    // Bind HQ change event
    bindHeadquarterChange();
    
    // Update status cards
    updateStatusCards();
}

function initializeSelect2() {
    $('.station-select').each(function() {
        if (!$(this).data('select2')) {
            $(this).select2({
                width: '100%',
                placeholder: 'Select station...',
                allowClear: true,
            });
        }
    });
    
    $('.employee-select').each(function() {
        if (!$(this).data('select2')) {
            $(this).select2({
                width: '100%',
                placeholder: 'Select colleague(s)',
                allowClear: true,
            });
        }
    });
}

function bindHeadquarterChange() {
    // No per-row HQ selection anymore - it's at the top level
    // This function kept for compatibility but not used for HQ changes
}

// Update status cards with counts
function updateStatusCards() {
    const month = $('#month-selector').val();
    if (!month) return;
    
    const [year, monthNum] = month.split('-');
    const daysInMonth = new Date(year, monthNum, 0).getDate();
    
    let approvedCount = 0;
    let pendingCount = 0;
    
    existingTours.forEach(tour => {
        let tourDate = tour.date;
        if (tourDate.includes('T')) tourDate = tourDate.split('T')[0];
        
        // Check if tour is in selected month
        if (tourDate.startsWith(month)) {
            if (tour.status === 'approved') {
                approvedCount++;
            } else {
                pendingCount++;
            }
        }
    });
    
    const emptyCount = daysInMonth - (approvedCount + pendingCount);
    
    // Update card values
    $('#approved-count').text(approvedCount);
    $('#pending-count').text(pendingCount);
    $('#empty-count').text(emptyCount);
    $('#total-days').text(daysInMonth);
    
    // Show cards only if there are existing tours
    if (approvedCount > 0 || pendingCount > 0) {
        $('#status-cards').fadeIn();
    } else {
        $('#status-cards').hide();
    }
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Show info message for employees on page load
if (!isAdmin && userHeadquarter) {
    const empHq = headquarters.find(h => h.id == userHeadquarter);
    if (empHq) {
        Swal.fire({
            icon: 'info',
            title: 'Employee Tour Plan',
            html: `<strong>Your HeadQuarter:</strong> ${empHq.name}<br><br>
                   <small>Stations loaded. Select stations and work status for each day.</small>`,
            timer: 3500,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    }
} 
else if (!isAdmin && !userHeadquarter) {
    Swal.fire({
        icon: 'error',
        title: 'No HeadQuarter Assigned',
        text: 'Please contact your administrator to assign a headquarter to your profile.',
        confirmButtonText: 'OK'
    });
}

// Quick Fill All Days
$('#quick-fill-all').click(function() {
    const hqId = isAdmin ? $('#headquarter-selector').val() : userHeadquarter;
    
    if (!hqId) {
        Swal.fire({
            icon: 'warning',
            title: 'Select HeadQuarter First',
            text: 'Please select a headquarter before quick filling'
        });
        return;
    }
    
    Swal.fire({
        title: 'Quick Fill All Weekdays',
        html: `
            <div class="text-left">
                <div class="form-group mb-3">
                    <label><strong>Work Type</strong></label>
                    <select class="form-control" id="quick-work-status">
                        <option value="">-- Select --</option>
                        ${workStatuses.map(ws => `<option value="${ws.name}">${ws.name}</option>`).join('')}
                    </select>
                </div>
                <small class="text-muted"><i class="fa fa-info-circle"></i> This will fill all weekdays (Mon-Sat) with selected work type. Sundays will be skipped.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> Fill All Weekdays',
        cancelButtonText: 'Cancel',
        width: '500px',
        preConfirm: () => {
            const workStatus = $('#quick-work-status').val();
            if (!workStatus) {
                Swal.showValidationMessage('Please select a work type');
                return false;
            }
            return { workStatus };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            let filled = 0;
            $('tr[data-day]').each(function() {
                const $row = $(this);
                if (!$row.hasClass('sunday-row')) {
                    const day = $row.data('day');
                    $(`select[name="work_status_${day}"]`).val(result.value.workStatus);
                    filled++;
                }
            });
            
            Swal.fire({
                icon: 'success',
                title: 'Done!',
                html: `<strong>${filled} weekdays</strong> filled with <strong>"${result.value.workStatus}"</strong>`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
});

// Copy from Previous Day
$(document).on('click', '.copy-prev-day', function() {
    const day = $(this).data('day');
    const prevDay = day - 1;
    
    // Copy work status
    const prevWorkStatus = $(`select[name="work_status_${prevDay}"]`).val();
    $(`select[name="work_status_${day}"]`).val(prevWorkStatus);
    
    // Copy station (Select2)
    const prevStation = $(`select[name="station_${prevDay}"]`).val();
    if (prevStation) {
        $(`select[name="station_${day}"]`).val(prevStation).trigger('change');
    }
    
    // Copy work with (Select2)
    const prevWorkWith = $(`select[name="work_with_${prevDay}[]"]`).val();
    if (prevWorkWith && prevWorkWith.length > 0) {
        $(`select[name="work_with_${day}[]"]`).val(prevWorkWith).trigger('change');
    }
    
    // Copy remark
    const prevRemark = $(`input[name="remark_${prevDay}"]`).val();
    $(`input[name="remark_${day}"]`).val(prevRemark);
    
    // Visual feedback
    $(this).html('<i class="fa fa-check text-success"></i>').prop('disabled', true);
    setTimeout(() => {
        $(this).html('<i class="fa fa-copy"></i>').prop('disabled', false);
    }, 1000);
});

// Clear all form
$('#clear-form').click(function() {
    Swal.fire({
        title: 'Clear all data?',
        text: 'This will reset the entire form',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            generateMonthlyCalendar($('#month-selector').val());
        }
    });
});

// Auto-generate calendar on page load
$(document).ready(function() {
    // Initialize select-picker for admin HQ dropdown
    if (isAdmin) {
        $('#headquarter-selector').selectpicker('refresh');
        
        // Show info for admin to select HQ
        if (!$('#headquarter-selector').val()) {
            Swal.fire({
                icon: 'info',
                title: 'Select HeadQuarter',
                text: 'Please select a headquarter to populate stations and employees',
                timer: 3000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }
    }
    
    generateMonthlyCalendar($('#month-selector').val());
    
    // Show info if there are already submitted tours
    if (existingTours.length > 0) {
        const approvedCount = existingTours.filter(t => t.status === 'approved').length;
        const pendingCount = existingTours.filter(t => t.status === 'pending').length;
        
        Swal.fire({
            icon: 'info',
            title: 'Existing Tours Found',
            html: `You have already submitted tours:<br>
                   <strong class="text-success">${approvedCount} Approved</strong> (locked)<br>
                   <strong class="text-warning">${pendingCount} Pending</strong> (locked)<br><br>
                   <small>Locked dates cannot be re-submitted. To edit, go to <strong>Tour Plan Status</strong>.</small>`,
            timer: 5000,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
    }
    
    // For employees, populate stations after calendar is generated
    setTimeout(function() {
        // if (!isAdmin && userHeadquarter) {
        //     console.log('Employee HQ auto-populate, userHeadquarter:', userHeadquarter);
        //     const empHq = headquarters.find(h => h.id == userHeadquarter);
        //     console.log('Found employee HQ object:', empHq);
        //     if (empHq) {
        //         console.log('Populating stations for employee HQ:', empHq.name);
        //         console.log('HQ has', empHq.exstations?.length || 0, 'ex-stations and', empHq.outstations?.length || 0, 'out-stations');
        //         populateStationsForAllDays(empHq);
        //     } else {
        //         console.error('Employee HQ not found in headquarters array');
        //     }
        // }
           if (!isAdmin) {
                if (userHeadquarter && headquarters.length === 1) {
                    populateStationsForAllDays(headquarters[0]);
                }
                if (headquarters.length > 1) {
                    populateStationsForAllDaysForMultipleHQs();
                }
            }
    }, 500);
});

// Form submission with validation
$('#save-tour-plan').click(function(e) {
    e.preventDefault();
    
    // Validate HQ is selected/assigned
    const hqId = isAdmin ? $('#headquarter-selector').val() : userHeadquarter;
    if (!hqId) {
        Swal.fire({
            icon: 'error',
            title: 'HeadQuarter Required',
            text: 'Please select a headquarter before submitting'
        });
        return;
    }
    
    // Validate "Submit To" manager is selected
    const submittedTo = $('#submitted-to-selector').val();
    if (!submittedTo) {
        Swal.fire({
            icon: 'error',
            title: 'Manager Required',
            text: 'Please select a manager to submit the tour plan to'
        });
        return;
    }
    
    // VALIDATE: Entire month must be filled
    const month = $('#month-selector').val();
    const [year, monthNum] = month.split('-');
    const daysInMonth = new Date(year, monthNum, 0).getDate();
    
    let emptyDays = [];
    let filledDays = 0;
    let lockedDays = 0;
    
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, monthNum - 1, day);
        const dateStr = formatDate(date);
        const existingTour = existingToursMap[dateStr];
        
        // Skip if already submitted (locked)
        if (existingTour) {
            lockedDays++;
            continue;
        }
        
        // Check if this day has data filled
        const workStatus = $(`select[name="work_status_${day}"]`).val();
        const station = $(`select[name="station_${day}"]`).val();
        const workWith = $(`select[name="work_with_${day}[]"]`).val();
        const remark = $(`input[name="remark_${day}"]`).val();
        
        // All three fields are mandatory: Work Type, Station, and Work With
        if (!workStatus || !station || !workWith || (workWith && workWith.length === 0)) {
            emptyDays.push(day);
        } else {
            filledDays++;
        }
    }
    
    // Total days that should be filled (excluding locked)
    const requiredDays = daysInMonth - lockedDays;
    
    // Show error if not all days are filled
    if (emptyDays.length > 0) {
        const emptyDaysList = emptyDays.length > 10 
            ? emptyDays.slice(0, 10).join(', ') + ` ... and ${emptyDays.length - 10} more`
            : emptyDays.join(', ');
        
        Swal.fire({
            icon: 'error',
            title: 'Incomplete Tour Plan',
            html: `<div class="text-left">
                   <strong class="text-danger">You must fill the entire month!</strong><br><br>
                   • <strong>Total Days in Month:</strong> ${daysInMonth}<br>
                   • <strong>Already Submitted:</strong> ${lockedDays} days (locked)<br>
                   • <strong>Filled:</strong> ${filledDays} days<br>
                   • <strong class="text-danger">Empty:</strong> ${emptyDays.length} days<br><br>
                   <strong class="text-danger">Empty Days: ${emptyDaysList}</strong><br><br>
                   </div>
                   <small class="text-muted">Please select <strong>Work Type</strong>, <strong>Station</strong>, and <strong>Work With</strong> for all days before submitting.</small>`,
            confirmButtonText: 'OK, I will fill them'
        });
        return;
    }
    
    // All validation passed - proceed with submission
    Swal.fire({
        title: 'Submit Complete Tour Plan?',
        html: `<strong>Month:</strong> ${month}<br>
               <strong>Days to Submit:</strong> ${filledDays} (${lockedDays} already submitted)<br>
               <strong>Submit To:</strong> ${$('#submitted-to-selector option:selected').text()}<br><br>
               <small class="text-success">✓ All days are filled!</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Use AJAX submission for proper redirect handling
            $.easyAjax({
                url: "{{ route('tours.store') }}",
                container: '#tour-plan-form',
                type: "POST",
                data: $('#tour-plan-form').serialize(),
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-tour-plan",
                redirect: true,
                success: function(response) {
                    if (response.status == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Tour Plan Saved!',
                            text: 'Tour plan created successfully for the month',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // Redirect after brief delay
                        setTimeout(function() {
                            window.location.href = "{{ route('tours.index') }}";
                        }, 1500);
                    }
                }
            });
        }
    });
});
function populateStationsForAllDaysForMultipleHQs() {

    $('select[name^="station_"]').each(function () {

        const $stationSelect = $(this);

        if ($stationSelect.data('select2')) {
            $stationSelect.select2('destroy');
        }

        $stationSelect.empty().append('<option value="">Select station...</option>');

        headquarters.forEach(hq => {

            // HQ
            $stationSelect.append(
                `<option value="HQ_${hq.id}">
                    ${hq.name} (Headquarter)
                 </option>`
            );

            // Ex-Stations
            (hq.exstations || []).forEach(ex => {
                $stationSelect.append(
                    `<option value="EX_${ex.id}">
                        ${ex.name} (Ex-Station)
                     </option>`
                );
            });

            // Out-Stations
            (hq.outstations || []).forEach(out => {
                $stationSelect.append(
                    `<option value="OUT_${out.id}">
                        ${out.name} (Out-Station)
                     </option>`
                );
            });
        });

        $stationSelect.select2({
            width: '100%',
            placeholder: 'Select station...',
            allowClear: true
        });
    });
}

</script>

@endpush
