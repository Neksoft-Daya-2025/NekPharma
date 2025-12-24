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
            <div class="row mb-4">
                @if(user()->hasRole('admin'))
                    <div class="col-md-3">
                        <label for="employee-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                            Select Employee <sup class="f-14 mr-1 text-danger">*</sup>
                        </label>
                        <select class="form-control height-35 f-14 select-picker" name="employee_id" id="employee-selector" data-live-search="true">
                            <option value="all" {{ (!$selectedEmployeeId || $selectedEmployeeId == 'all') ? 'selected' : '' }}>-- All Employees --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $selectedEmployeeId == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                    @if($emp->designation) ({{ $emp->designation->name ?? '' }}) @endif
                                </option>
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
                </div>
                <!--<div class="col-md-3">-->
                <!--    <label for="headquarter-selector" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">-->
                <!--        HeadQuarter <sup class="f-14 mr-1 text-danger">*</sup>-->
                <!--    </label>-->
                <!--    @if(user()->hasRole('admin'))-->
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
                
                    @if(!$headquarters->isEmpty())
   <select name="headquarter" id="headquarter-selector"
        class="form-control select-picker" required>

    <option value="">-- Select HeadQuarter --</option>

    @foreach($headquarters as $hq)
        <option value="{{ $hq->id }}"
            {{ isset($selectedHeadquarter) && $selectedHeadquarter == $hq->id ? 'selected' : '' }}>
            {{ $hq->name }} ({{ $hq->area->name ?? '' }})
        </option>
    @endforeach
</select>

@endif

                </div>
<!--================================================================================================-->
                <div class="col-md-{{ user()->hasRole('admin') ? '3' : '6' }} d-flex align-items-end pb-3">
                    <x-forms.link-primary :link="route('tours.create')" class="mr-2" icon="plus">
                        <i class="fa fa-calendar-plus"></i> Create New Month
                    </x-forms.link-primary>
                    <button type="button" class="btn btn-secondary btn-sm mr-2" id="clear-form">
                        <i class="fa fa-eraser"></i> Clear All
                    </button>
                </div>
            </div>
            
            <!-- Action Buttons Row -->
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    @if(user()->permission('approve_tours') == 'all')
                        <button type="button" class="btn btn-success" id="approve-all-btn">
                            <i class="fa fa-check-circle"></i> Approve All Tours
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
                                    @if(user()->permission('delete_tours') == 'all')
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
const employees = @json($employees);
const userHeadquarter = {{ $userHeadquarter ?? 'null' }};
const existingTours = @json($tours);
const isAdmin = {{ user()->permission('view_tours') == 'all' ? 'true' : 'false' }};
const canApprove = {{ user()->permission('approve_tours') == 'all' ? 'true' : 'false' }};
const canBulkDelete = {{ user()->permission('delete_tours') == 'all' ? 'true' : 'false' }};
const currentUserId = {{ user()->id }};
const managers = @json($managers);
const selectedEmployeeId = '{{ $selectedEmployeeId ?? 'all' }}';

// Debug: Show raw data from backend
console.log('=== RAW DATA FROM BACKEND ===');
console.log('Is Admin:', isAdmin);
console.log('Can Approve:', canApprove);
console.log('Total tours loaded:', existingTours.length);
if (existingTours.length > 0) {
    console.log('First tour sample:', existingTours[0]);
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
    
    // Get default HQ: for admin from selector, for employee from assigned HQ
    let defaultHqId = userHeadquarter;
    if (isAdmin) {
        defaultHqId = $('#headquarter-selector').val();
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
                // Pending tours can be edited by submitter or designated approver
                const isSubmittedToMe = existingTour.submitted_to && existingTour.submitted_to.id == currentUserId;
                isDisabled = !canApprove && !isSubmittedToMe && existingTour.user_id != currentUserId;
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
        
        // Work With Multi-select - Parse comma-separated values
        let selectedWorkWith = [];
        if (existingTour && existingTour.work_with) {
            selectedWorkWith = existingTour.work_with.split(',').map(s => s.trim());
        }
        
        const workWithOptions = employees.map(emp => {
            const selected = selectedWorkWith.includes(emp.id.toString()) ? 'selected' : '';
            return `<option value="${emp.id}" ${selected}>${emp.name}</option>`;
        }).join('');
        
        const remarkValue = existingTour ? (existingTour.remark || '') : '';
        const tourId = existingTour ? existingTour.id : '';
        
        // Submitted By and Submit To columns
        const submittedByName = existingTour && existingTour.user ? existingTour.user.name : '-';
        const submittedToName = existingTour && existingTour.submitted_to ? existingTour.submitted_to.name : '-';
        
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
        const checkboxCell = canBulkDelete && existingTour ? `
            <td class="text-center">
                <input type="checkbox" class="tour-checkbox" value="${tourId}" data-tour-id="${tourId}">
            </td>
        ` : (canBulkDelete ? '<td></td>' : '');
        
        $tbody.append(`
            <tr class="${rowClass}" data-tour-id="${tourId}" data-date="${dateStr}">
                ${checkboxCell}
                <td class="text-center">
                    ${dayDisplay}
                    ${statusIndicator ? '<br>' + statusIndicator : ''}
                </td>
                <td><input type="date" class="form-control form-control-sm" name="date_${day}" value="${dateStr}" readonly></td>
                <td><input type="text" class="form-control form-control-sm" name="day_${day}" value="${dayName}" readonly></td>
                <td class="text-center"><small>${submittedByName}</small></td>
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
                    <select class="form-control select-workwith" name="work_with_${day}" multiple style="width: 100%;" ${disabledAttr}>
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
            $(this).select2({
                width: '100%',
                placeholder: 'Select station...',
                allowClear: true
            });
        });
        
        $('.select-workwith').each(function() {
            $(this).select2({
                width: '100%',
                placeholder: 'Select colleagues...',
                allowClear: true
            });
        });
        
        // Debug: Log first row to verify selections
        console.log('First station select:', $('.select-station').first().val());
        console.log('First workwith select:', $('.select-workwith').first().val());
    }, 100);
}

// Employee selector change (Admin only) - reload page with filter
$('#employee-selector').on('change', function() {
    const employeeId = $(this).val();
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
});

// Individual tour checkbox
$('body').on('change', '.tour-checkbox', function() {
    updateBulkDeleteButton();
    
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

// Save/Update tour plan
$('#save-tour-plan').on('click', function(e) {
    e.preventDefault();
    $.easyAjax({
        url: "{{ route('tours.store') }}",
        container: '#tour-plan-form',
        type: "POST",
        data: $('#tour-plan-form').serialize(),
        success: function(response) {
            if (response.status == "success") {
                window.location.reload();
            }
        }
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
    
    // For employees, auto-select their HQ
    if (userHeadquarter && !isAdmin) {
        console.log('Auto-loading for employee HQ:', userHeadquarter);
    }
    
    // For admin, wait for HQ selection OR load with first available HQ
    if (isAdmin && headquarters.length > 0) {
        // Pre-select first HQ if none selected
        if (!$('#headquarter-selector').val() && userHeadquarter) {
            $('#headquarter-selector').val(userHeadquarter);
            $('#headquarter-selector').selectpicker('refresh');
        }
    }
    
    // Auto-load tours immediately
    loadToursForMonth(currentMonth);
});
</script>
@endpush
