@extends('layouts.app')

@push('styles')
<style>
    .dcr-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        margin-bottom: 30px;
    }
    
    .tour-alert {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
        font-weight: 600;
    }
    
    .section-header {
        background: linear-gradient(135deg, #{{ company()->header_color }} 0%, #667eea 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin: 30px 0 20px 0;
        font-weight: 700;
    }
    
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    
    label {
        font-weight: 600;
        font-size: 13px;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .form-control {
        border-radius: 5px;
        border: 1px solid #d1d3e2;
    }
    
    .form-control:focus {
        border-color: #8bab4c;
        box-shadow: 0 0 0 0.2rem rgba(139, 171, 76, 0.25);
    }
    
    #field-work-sections, #non-field-work-section {
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .section-header {
        background: linear-gradient(135deg, #8bab4c 0%, #7a9a3c 100%);
    }
    
    .table thead {
        background: linear-gradient(135deg, #8bab4c 0%, #7a9a3c 100%);
        color: white;
    }
    
    .table thead th {
        font-weight: 600;
        border: none;
    }
    
    /* Dynamic visit cards styling */
    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        border-bottom: 2px solid #dee2e6;
    }
    
    .doctor-visit-row {
        animation: slideIn 0.3s ease;
    }
    
    .chemist-visit-row {
        animation: slideIn 0.3s ease;
    }
    
    .stockist-visit-row {
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .table-responsive table {
        margin-bottom: 0;
    }
    
    .table-responsive table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
    }
    
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    label.font-weight-bold {
        color: #495057;
        font-size: 13px;
        margin-bottom: 5px;
    }
    
    .form-control:focus {
        border-color: #8bab4c;
        box-shadow: 0 0 0 0.2rem rgba(139, 171, 76, 0.25);
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <!-- Header -->
    <div class="dcr-header">
        <h2 class="mb-0"><i class="fa fa-clipboard-list"></i> Submit Daily Call Report (DCR)</h2>
        <p class="mb-0 mt-2">Record your daily field activities and customer visits</p>
    </div>
        
    <form action="{{ route('dcr-reports.store') }}" method="POST" id="dcr-form">
            @csrf
            
            <!-- Report Information Section -->
            <div class="form-section">
                <h5 class="mb-4"><i class="fa fa-info-circle text-primary"></i> Report Information</h5>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Reporting Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="report_date" id="report_date" 
                               value="{{ $reportDate }}" required>
                               <!--<input type="date" class="form-control" name="report_date" id="report_date" -->
                               <!--value="{{ $reportDate }}" required max="{{ now()->format('Y-m-d') }}">-->
                        <small class="text-muted">Auto-loaded: First pending date</small>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>HeadQuarter <span class="text-danger">*</span></label>
                        @if(user()->hasRole('admin') || user()->hasRole('area-business-manager') || user()->hasRole('regional-business-manager'))
                            <select class="form-control select-picker" name="headquarter_id" id="headquarter_id" data-live-search="true" required>
                                <option value="">-- Select HeadQuarter --</option>
                                @foreach($headquarters as $hq)
                                    <option value="{{ $hq->id }}" {{ $userHeadquarter == $hq->id ? 'selected' : '' }}>
                                        {{ $hq->name }}
                                        @if($hq->area) ({{ $hq->area->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select HQ for this DCR report</small>
                        @else
                            @if($userHeadquarter)
                                <input type="hidden" name="headquarter_id" value="{{ $userHeadquarter }}">
                                <div class="form-control bg-light" style="display: flex; align-items: center;">
                                    <span class="badge badge-success mr-2">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                    {{ $headquarters->find($userHeadquarter)->name ?? '' }}
                                </div>
                                <small class="text-muted">Your assigned headquarter</small>
                            @else
                                <div class="form-control bg-danger text-white">
                                    <i class="fa fa-exclamation-triangle"></i> Not Assigned
                                </div>
                                <small class="text-danger">Contact admin to assign a headquarter</small>
                            @endif
                        @endif
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Work Type <span class="text-danger">*</span></label>
                        <select class="form-control select-picker" name="work_status" id="work_status" data-live-search="true" required>
                            <option value="">Select Work Type</option>
                            @foreach(\App\Models\TourWorkStatus::where('is_active', true)->orderBy('name')->get() as $status)
                                <option value="{{ $status->name }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">From tour plan, editable</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Select Station</label>
                        <select class="form-control select-picker" name="station" id="station" data-live-search="true">
                            <option value="">Select station...</option>
                            @if($userHeadquarter)
                                @php
                                    $hq = $headquarters->find($userHeadquarter);
                                @endphp
                                @if($hq)
                                    <option value="{{ $hq->name }}">{{ $hq->name }} (Headquarter)</option>
                                    @if($hq->exstations)
                                        @foreach($hq->exstations as $exstation)
                                            <option value="{{ $exstation->name }}">{{ $exstation->name }} (Ex-Station)</option>
                                        @endforeach
                                    @endif
                                    @if($hq->outstations)
                                        @foreach($hq->outstations as $outstation)
                                            <option value="{{ $outstation->name }}">{{ $outstation->name }} (Out-Station)</option>
                                        @endforeach
                                    @endif
                                @endif
                            @endif
                        </select>
                        <small class="text-muted">Select station (includes HQ)</small>
                    </div>
                </div>
                
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Work With</label>
                        <select class="form-control select-picker" name="work_with[]" id="work_with" data-live-search="true" multiple>
                            @foreach(\App\Models\User::where('company_id', company()->id)->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select multiple people to work with</small>
                    </div>
                </div>
            </div>
            
            <!-- Tour Plan Alert -->
            <div class="tour-alert" id="tour-alert" style="display: none;">
                <i class="fa fa-check-circle"></i> <span id="tour-alert-text"></span>
            </div>
        </div>
        
        <!-- Non-Field Work Remark Section (Hidden by default) -->
        <div class="form-section" id="non-field-work-section" style="display: none;">
            <h5 class="mb-4"><i class="fa fa-comment-alt text-info"></i> Day Remark</h5>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remark <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="remark" id="non_field_remark" rows="4" 
                                  placeholder="Enter details about your day (meeting, conference, leave, etc.)"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field Work Sections (Hidden by default) -->
        <div id="field-work-sections" style="display: none;">
        
        <!-- Doctor Section -->
        <div class="section-header d-flex justify-content-between align-items-center">
            <div><i class="fa fa-user-md"></i> Doctor Visit Details</div>
            <button type="button" class="btn btn-success btn-sm" id="add-doctor-btn">
                <i class="fa fa-plus"></i> Add Doctor Visit
            </button>
        </div>
        
        <div id="doctor-visits-container">
            <!-- Doctor visits will be added here dynamically -->
        </div>

        <!-- Chemist Section -->
        <div class="section-header d-flex justify-content-between align-items-center">
            <div><i class="fa fa-flask"></i> Chemist Visit Details</div>
            <button type="button" class="btn btn-success btn-sm" id="add-chemist-btn">
                <i class="fa fa-plus"></i> Add Chemist Visit
            </button>
        </div>
        
        <div id="chemist-visits-container">
            <!-- Chemist visits will be added here dynamically -->
        </div>

        <!-- Stockist Section -->
        <div class="section-header d-flex justify-content-between align-items-center">
            <div><i class="fa fa-warehouse"></i> Stockist Visit Details</div>
            <button type="button" class="btn btn-success btn-sm" id="add-stockist-btn">
                <i class="fa fa-plus"></i> Add Stockist Visit
            </button>
        </div>
        
        <div id="stockist-visits-container">
            <!-- Stockist visits will be added here dynamically -->
        </div>
        
        </div><!-- End Field Work Sections -->

        <!-- Submit Buttons -->
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save DCR Report</button>
            <a href="{{ route('dcr-reports.index') }}" class="btn btn-secondary btn-lg ml-2"><i class="fa fa-times"></i> Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const isAdmin = {{ user()->hasRole('admin') ? 'true' : 'false' }};
const ALL_HEADQUARTERS = @json($headquarters);

function getHQById(hqId) {
    if (!hqId) return null;
    return ALL_HEADQUARTERS.find(h => Number(h.id) === Number(hqId)) || null;
}

$(document).ready(function() {
    // Initialize select pickers
    $('.select-picker').selectpicker();
    
    // ======================================= sonu ==========================================================
    const initialHQ = $('#headquarter_id').val() || '{{ $userHeadquarter }}';
if (initialHQ) {
    populateStationsByHQ(initialHQ);
}

    let selectedStationType = null;
let selectedStationId   = null;

$('#station').on('changed.bs.select', function () {
    const selectedText = $(this).find(':selected').text().trim();
    const hqId = $('#headquarter_id').val() || '{{ $userHeadquarter }}';

    const hq = getHQById(hqId);
    if (!hq) {
        console.warn('HQ not found');
        return;
    }

    // HEADQUARTER
    if (selectedText.includes('(Headquarter)')) {
        selectedStationType = 'headquarter';
        selectedStationId = hq.id;
    }

    // EX-STATION
    else if (selectedText.includes('(Ex-Station)')) {
        const name = selectedText.replace('(Ex-Station)', '').trim();
        const ex = (hq.exstations || []).find(s => s.name === name);
        if (!ex) return;
        selectedStationType = 'exstation';
        selectedStationId = ex.id;
    }

    // OUT-STATION
    else if (selectedText.includes('(Out-Station)')) {
        const name = selectedText.replace('(Out-Station)', '').trim();
        const out = (hq.outstations || []).find(s => s.name === name);
        if (!out) return;
        selectedStationType = 'outstation';
        selectedStationId = out.id;
    }

    console.log('Station Selected:', selectedStationType, selectedStationId);

    fetchFilteredLists();
});

    
    function fetchFilteredLists() {
    if (!selectedStationType || !selectedStationId) {
        console.warn('Station not resolved yet');
        return;
    }

    $.easyAjax({
        url: "{{ route('dcr.get-station-customers') }}",
        type: "GET",
        data: {
            station_type: selectedStationType,
            station_id: selectedStationId
        },
        success: function(res) {

        window.filteredDoctors   = res.doctors   || [];
        window.filteredChemists  = res.chemists  || [];
        window.filteredStockists = res.stockists || [];
    
        console.log('Doctors loaded:', window.filteredDoctors.length);
    
        // 🔥 THIS WAS MISSING
        // refreshDoctorDropdowns();
        // refreshChemistDropdowns();
        // refreshStockistDropdowns();
        // CLEAR OLD VISITS
    $('#doctor-visits-container').empty();
    $('#chemist-visits-container').empty();
    $('#stockist-visits-container').empty();

    // ADD VISITS ONLY NOW (DATA EXISTS)
    addDoctorVisit();
    addChemistVisit();
    addStockistVisit();
    }

    });
}

function refreshDoctorDropdowns() {
    $('.doctor-select').each(function () {
        const $select = $(this);

        $select.selectpicker('destroy'); // 🔥 REQUIRED

        let options = '<option value="">-- Select Doctor --</option>';

        (window.filteredDoctors || []).forEach(d => {
            const stationName =
                d.exstation?.name ||
                d.outstation?.name ||
                d.headquarter?.name ||
                '';

            options += `
                <option value="${d.id}"
                    data-speciality="${d.speciality || ''}"
                    data-station-name="${stationName}">
                    ${d.fullname}
                </option>`;
        });

        $select.html(options);
        $select.selectpicker(); // 🔥 RE-INIT
    });
}


function refreshChemistDropdowns() {
    $('.chemist-select').each(function () {
        const $select = $(this);

        $select.selectpicker('destroy');

        let options = '<option value="">-- Select Chemist --</option>';

        (window.filteredChemists || []).forEach(c => {
            const stationName =
                c.exstation?.name ||
                c.outstation?.name ||
                c.headquarter?.name ||
                '';

            options += `
                <option value="${c.id}"
                    data-station-name="${stationName}">
                    ${c.shopname}
                </option>`;
        });

        $select.html(options);
        $select.selectpicker();
    });
}


function refreshStockistDropdowns() {
    $('.stockist-select').each(function () {
        const $select = $(this);

        $select.selectpicker('destroy');

        let options = '<option value="">-- Select Stockist --</option>';

        (window.filteredStockists || []).forEach(s => {
            const stationName =
                s.exstation?.name ||
                s.outstation?.name ||
                s.headquarter?.name ||
                '';

            options += `
                <option value="${s.id}"
                    data-station-name="${stationName}">
                    ${s.shopname}
                </option>`;
        });

        $select.html(options);
        $select.selectpicker();
    });
}






    // =======================================================================================================
    
    // Toggle sections based on work type
    function toggleSectionsByWorkStatus() {
        const selectedStatus = $('#work_status').val();
        
        console.log('Work Type changed:', selectedStatus);
        
        // Show field sections for "Field Work" only
        if (selectedStatus === 'Field Work') {
            // Show doctor, chemist, stockist sections
            $('#field-work-sections').slideDown();
            $('#non-field-work-section').slideUp();
            $('#non_field_remark').prop('required', false);
        } else if (selectedStatus) {
            // Show only remark section for non-field work
            $('#field-work-sections').slideUp();
            $('#non-field-work-section').slideDown();
            $('#non_field_remark').prop('required', true);
        } else {
            // Nothing selected, hide all
            $('#field-work-sections').slideUp();
            $('#non-field-work-section').slideUp();
            $('#non_field_remark').prop('required', false);
        }
    }
    
    // Watch for work status changes
    $('#work_status').on('change', function() {
        toggleSectionsByWorkStatus();
        
        // Auto-add first visit when Field Work is selected
        const selectedStatus = $(this).val();
        // if (selectedStatus === 'Field Work') {
        //     // Add one of each if containers are empty
        //     if ($('#doctor-visits-container').children().length === 0) {
        //         addDoctorVisit();
        //     }
        //     if ($('#chemist-visits-container').children().length === 0) {
        //         addChemistVisit();
        //     }
        //     if ($('#stockist-visits-container').children().length === 0) {
        //         addStockistVisit();
        //     }
        // }
    });
    
    // Doctor visits management
    let doctorVisitCounter = 0;
    const allDoctors = @json($doctors);
    const allChemists = @json($chemists);
    const allStockists = @json($stockists);
    const allProducts = @json($products);
    
    // Debug: Check if area is loaded
    console.log('Doctors with area:', allDoctors.length > 0 ? allDoctors[0] : 'No doctors');
    
    $('#add-doctor-btn').on('click', function() {
        addDoctorVisit();
    });
    
    function addDoctorVisit() {
        doctorVisitCounter++;
        const visitId = doctorVisitCounter;
        const doctorHtml = `
            <div class="card shadow-sm mb-4 doctor-visit-row" data-visit-id="${visitId}" style="border-left: 4px solid #8bab4c;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">
                        <span class="badge badge-primary mr-2">#${visitId}</span>
                        <i class="fa fa-user-md text-primary"></i> Doctor Visit
                    </h6>
                    <button type="button" class="btn btn-danger btn-sm remove-doctor-visit" data-visit-id="${visitId}">
                        <i class="fa fa-trash"></i> Remove
                    </button>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Dr. <span class="text-danger">*</span></label>
                            <select class="form-control select-picker doctor-select" name="doctors[${visitId}][doctor_id]" data-visit-id="${visitId}" data-live-search="true" required>
                                <option value="">-- Select Doctor --</option>
                               ${
    (window.filteredDoctors || [])
        .map(function(d) {
            
            const areaName = d.area ? (d.area.name || d.area) : (d.area_name || '');
            const hqName = d.headquarter ? d.headquarter.name : '';
            const exstationName = d.exstation ? d.exstation.name : '';
            const outstationName = d.outstation ? d.outstation.name : '';
            const stationName = exstationName || outstationName || hqName;
            return `
                <option value="${d.id}"
                    data-speciality="${d.speciality || ''}"
                    data-area="${areaName}"
                    data-station-name="${stationName}">
                    ${d.fullname}
                </option>
            `;
        })
        .join('')
}

                            </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Speciality</label>
                                <input type="text" class="form-control bg-light doctor-speciality" name="doctors[${visitId}][speciality]" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Station</label>
                                <input type="text" class="form-control bg-light doctor-station" name="doctors[${visitId}][station_info]" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="font-weight-bold">MSL Number</label>
                                <input type="number" class="form-control" doctor-msl name="doctors[${visitId}][msl]" value="0" min="0" max="10" placeholder="0-10">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="text-primary mb-3"><i class="fa fa-pills"></i> Products Promoted</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="30%">Product</th>
                                    <th width="15%" class="text-center">Samples Unit</th>
                                    <th width="15%" class="text-center">POB</th>
                                    <th width="40%">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${[1, 2, 3].map(i => `
                                    <tr>
                                        <td>
                                            <select class="form-control" name="doctors[${visitId}][product${i}]">
                                                <option value="">-- Select Product ${i} --</option>
                                                ${allProducts.map(p => `<option value="${p.name}">${p.name}</option>`).join('')}
                                            </select>
                                        </td>
                                        <td><input type="number" class="form-control text-center" name="doctors[${visitId}][samples_unit${i}]" value="0" min="0" placeholder="0"></td>
                                        <td><input type="number" class="form-control text-center" name="doctors[${visitId}][pob${i}]" value="0" step="0.01" placeholder="0.00"></td>
                                        <td><input type="text" class="form-control" name="doctors[${visitId}][remark${i}]" placeholder="Enter remark..."></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">General Remark</label>
                                <textarea class="form-control" name="doctors[${visitId}][general_remark]" rows="2" placeholder="Overall remarks for this doctor visit..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#doctor-visits-container').append(doctorHtml);
        $('.doctor-select[data-visit-id="' + visitId + '"]').selectpicker();
        
        // Auto-fill when doctor is selected
        $('.doctor-select[data-visit-id="' + visitId + '"]').on('changed.bs.select', function () {

    const selected = $(this).find('option:selected');
    const visitRow = $(this).closest('.doctor-visit-row');
    // ✅ Speciality
    visitRow.find('.doctor-speciality')
        .val(selected.data('speciality') || '');

    // ✅ Station
    visitRow.find('.doctor-station')
        .val(selected.data('station-name') || '');

    // ✅ MSL (THIS WAS MISSING)
    visitRow.find('.doctor-msl')
        .val(selected.data('msl') || 0);
        
        console.log({
    speciality: selected.data('speciality'),
    station: selected.data('station-name'),
    // msl: selected.data('msl')
});

});


    }
    
    // Remove doctor visit
    $(document).on('click', '.remove-doctor-visit', function() {
        const visitId = $(this).data('visit-id');
        $(`.doctor-visit-row[data-visit-id="${visitId}"]`).remove();
    });
    
    // Chemist visits management
    let chemistVisitCounter = 0;
    
    $('#add-chemist-btn').on('click', function() {
        addChemistVisit();
    });
    
    function addChemistVisit() {
        chemistVisitCounter++;
        const visitId = chemistVisitCounter;
        
        const chemistHtml = `
            <div class="card shadow-sm mb-4 chemist-visit-row" data-visit-id="${visitId}" style="border-left: 4px solid #28a745;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">
                        <span class="badge badge-success mr-2">#${visitId}</span>
                        <i class="fa fa-flask text-success"></i> Chemist Visit
                    </h6>
                    <button type="button" class="btn btn-danger btn-sm remove-chemist-visit" data-visit-id="${visitId}">
                        <i class="fa fa-trash"></i> Remove
                    </button>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Chemist <span class="text-danger">*</span></label>
                            <select class="form-control select-picker chemist-select" name="chemists[${visitId}][chemist_id]" data-visit-id="${visitId}" data-live-search="true" required>
                                <option value="">-- Select Chemist --</option>
                                ${
    (window.filteredChemists || [])
        .map(function(c) {

            const areaName = c.area ? (c.area.name || c.area) : (c.area_name || '');
            const hqName = c.headquarter ? c.headquarter.name : '';
            const exstationName = c.exstation ? c.exstation.name : '';
            const outstationName = c.outstation ? c.outstation.name : '';
            const stationName = exstationName || outstationName || hqName;

            return `
                <option value="${c.id}"
                    data-station-name="${stationName}">
                    ${c.shopname}
                </option>
            `;
        })
        .join('')
}

                                
                                
                                
                            </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Station</label>
                                <input type="text" class="form-control bg-light chemist-station-name" name="chemists[${visitId}][station]" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="font-weight-bold">MSL Number</label>
                                <input type="number" class="form-control" name="chemists[${visitId}][msl]" value="0" min="0" max="10" placeholder="0-10">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="text-success mb-3"><i class="fa fa-prescription-bottle"></i> RCPA (Retail Chemist Prescription Audit)</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40%">Product</th>
                                    <th width="15%" class="text-center">Amount</th>
                                    <th width="45%">Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${[1, 2, 3, 4].map(i => `
                                    <tr>
                                        <td>
                                            <select class="form-control" name="chemists[${visitId}][rcpa${i}]">
                                                <option value="">-- Select Product ${i} --</option>
                                                ${allProducts.map(p => `<option value="${p.name}">${p.name}</option>`).join('')}
                                            </select>
                                        </td>
                                        <td><input type="number" class="form-control text-center" name="chemists[${visitId}][pob_amount${i}]" value="0" step="0.01" placeholder="0.00"></td>
                                        <td><input type="text" class="form-control" name="chemists[${visitId}][remark${i}]" placeholder="Enter remark..."></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">General Remark</label>
                                <textarea class="form-control" name="chemists[${visitId}][general_remark]" rows="2" placeholder="Overall remarks for this chemist visit..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#chemist-visits-container').append(chemistHtml);
        $('.chemist-select[data-visit-id="' + visitId + '"]').selectpicker();
        
        // Auto-fill when chemist is selected
        $('.chemist-select[data-visit-id="' + visitId + '"]').on('changed.bs.select', function() {
            const selected = $(this).find(':selected');
            const visitRow = $(this).closest('.chemist-visit-row');
            visitRow.find('.chemist-station-name').val(selected.data('station-name') || '');
        });
    }
    
    // Remove chemist visit
    $(document).on('click', '.remove-chemist-visit', function() {
        const visitId = $(this).data('visit-id');
        $(`.chemist-visit-row[data-visit-id="${visitId}"]`).remove();
    });
    
    // Stockist visits management
    let stockistVisitCounter = 0;
    
    $('#add-stockist-btn').on('click', function() {
        addStockistVisit();
    });
    
    function addStockistVisit() {
        stockistVisitCounter++;
        const visitId = stockistVisitCounter;
        
        const stockistHtml = `
            <div class="card shadow-sm mb-4 stockist-visit-row" data-visit-id="${visitId}" style="border-left: 4px solid #ffc107;">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">
                        <span class="badge badge-warning mr-2">#${visitId}</span>
                        <i class="fa fa-warehouse text-warning"></i> Stockist Visit
                    </h6>
                    <button type="button" class="btn btn-danger btn-sm remove-stockist-visit" data-visit-id="${visitId}">
                        <i class="fa fa-trash"></i> Remove
                    </button>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Stockist <span class="text-danger">*</span></label>
                            <select class="form-control select-picker stockist-select" name="stockists[${visitId}][stockist_id]" data-visit-id="${visitId}" data-live-search="true" required>
                                <option value="">-- Select Stockist --</option>
                                ${
    (window.filteredStockists || [])
        .map(function(s) {

            const areaName = s.area ? (s.area.name || s.area) : (s.area_name || '');
            const hqName = s.headquarter ? s.headquarter.name : '';
            const exstationName = s.exstation ? s.exstation.name : '';
            const outstationName = s.outstation ? s.outstation.name : '';
            const stationName = exstationName || outstationName || hqName;

            return `
                <option value="${s.id}"
                    data-station-name="${stationName}">
                    ${s.shopname}
                </option>
            `;
        })
        .join('')
}

                            </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Station</label>
                                <input type="text" class="form-control bg-light stockist-station-name" name="stockists[${visitId}][station]" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="font-weight-bold">MSL Number</label>
                                <input type="number" class="form-control" name="stockists[${visitId}][msl]" value="0" min="0" max="10" placeholder="0-10">
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    <h6 class="text-warning mb-3"><i class="fa fa-box"></i> Business Details</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">POB Description</label>
                                <input type="text" class="form-control" name="stockists[${visitId}][pob]" placeholder="Enter POB details">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">POB Amount</label>
                                <input type="number" class="form-control" name="stockists[${visitId}][pob_amount]" value="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Proprietor Name</label>
                                <input type="text" class="form-control" name="stockists[${visitId}][proprietor]" placeholder="Enter proprietor name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Proprietor Mobile</label>
                                <input type="text" class="form-control" name="stockists[${visitId}][proprietor_mobile]" placeholder="Enter mobile number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">General Remark</label>
                                <textarea class="form-control" name="stockists[${visitId}][general_remark]" rows="2" placeholder="Overall remarks for this stockist visit..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#stockist-visits-container').append(stockistHtml);
        $('.stockist-select[data-visit-id="' + visitId + '"]').selectpicker();
        
        // Auto-fill when stockist is selected
        $('.stockist-select[data-visit-id="' + visitId + '"]').on('changed.bs.select', function() {
            const selected = $(this).find(':selected');
            const visitRow = $(this).closest('.stockist-visit-row');
            visitRow.find('.stockist-station-name').val(selected.data('station-name') || '');
        });
    }
    
    // Remove stockist visit
    $(document).on('click', '.remove-stockist-visit', function() {
        const visitId = $(this).data('visit-id');
        $(`.stockist-visit-row[data-visit-id="${visitId}"]`).remove();
    });
    
    
    // Populate stations when HQ changes (for admin)
    $('#headquarter_id').on('changed.bs.select', function() {
        populateStationsByHQ($(this).val());
        const hqId = $(this).val();
        const $stationSelect = $('#station');
        
        // Clear existing options except the first one
        $stationSelect.find('option:not(:first)').remove();
        
        if (hqId) {
            const headquarters = @json($headquarters);
            const hq = headquarters.find(h => h.id == hqId);
            
            if (hq) {
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
                
                $stationSelect.selectpicker('refresh');
            }
        } else {
            $stationSelect.selectpicker('refresh');
        }
    });
    
    // Load tour plan when date changes
    $('#report_date').on('change', function() {
        const date = $(this).val();
        if (!date) return;
        
        $.easyAjax({
            url: "{{ route('dcr.get-tour-by-date') }}",
            type: "POST",
            data: {
                '_token': '{{ csrf_token() }}',
                'date': date
            },
            success: function(response) {
                if (response.status == 'success' && response.tour) {
                    const tour = response.tour;
                    const workTypeDisplay = tour.work_status || 'Field Work';
                    const stationDisplay = tour.station || tour.headquarter;
                    $('#tour-alert-text').html(`According to Tour Plan: <strong>${workTypeDisplay}</strong> at <strong>${stationDisplay}</strong> is Approved`);
                    $('#tour-alert').slideDown();
                    
                    // Auto-fill work status from tour plan
                    if (tour.work_status) {
                        // Take the first work status if comma-separated
                        const firstStatus = tour.work_status.split(',')[0].trim();
                        $('#work_status').val(firstStatus);
                        $('#work_status').selectpicker('refresh');
                        // Toggle sections based on loaded work status
                        toggleSectionsByWorkStatus();
                    }
                    
                    // Pre-fill station if available (single select now, take first value)
                    // if (tour.station) {
                    //     const stationValue = tour.station.split(',')[0].trim(); // Take first station if comma-separated
                    //     $('#station').val(stationValue);
                    //     $('#station').selectpicker('refresh');
                    // }
                    
                    if (tour.station) {
                        const stationValue = tour.station.split(',')[0].trim();
                    
                        $('#station')
                            .val(stationValue)
                            .selectpicker('refresh')
                            .trigger('changed.bs.select'); // 🔥 FORCE LOAD CUSTOMERS
                    }

                    
                    // Pre-fill work_with if available (handle comma-separated values)
                    if (tour.work_with) {
                        const workWithIds = tour.work_with.split(',').map(s => s.trim());
                        $('#work_with').val(workWithIds);
                        $('#work_with').selectpicker('refresh');
                    }
                } else {
                    $('#tour-alert').slideUp();
                    $('#work_status').val('');
                    $('#work_status').selectpicker('refresh');
                    toggleSectionsByWorkStatus();
                }
            }
        });
    });
    
    // Trigger on page load
    if ($('#report_date').val()) {
        $('#report_date').trigger('change');
    }
});
</script>
<script>
    function populateStationsByHQ(hqId) {

    const $station = $('#station');
    $station.find('option:not(:first)').remove();

    const hq = getHQById(hqId);
    if (!hq) return;

    $station.append(`<option value="${hq.name}">${hq.name} (Headquarter)</option>`);

    (hq.exstations || []).forEach(s => {
        $station.append(`<option value="${s.name}">${s.name} (Ex-Station)</option>`);
    });

    (hq.outstations || []).forEach(s => {
        $station.append(`<option value="${s.name}">${s.name} (Out-Station)</option>`);
    });

    $station.selectpicker('refresh');
}

</script>
@endpush

