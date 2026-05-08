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
        border-color: #{{ company()->header_color }};
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
        
    <form action="{{ route('dcr-management.store') }}" method="POST" id="dcr-form">
            @csrf
            
            <!-- Report Information Section -->
            <div class="form-section">
                <h5 class="mb-4"><i class="fa fa-info-circle text-primary"></i> Report Information</h5>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Reporting Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="report_date" id="report_date" 
                               value="{{ $reportDate }}" required max="{{ now()->format('Y-m-d') }}">
                        <small class="text-muted">Auto-loaded: First pending date</small>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>HeadQuarter <span class="text-danger">*</span></label>
                        @if(user()->hasRole('admin'))
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
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Work With</label>
                        <select class="form-control select-picker" name="work_with" id="work_with" data-live-search="true">
                            <option value="">Select or search workwith</option>
                            @foreach(\App\Models\User::where('company_id', company()->id)->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Tour Plan Alert -->
            <div class="tour-alert" id="tour-alert" style="display: none;">
                <i class="fa fa-check-circle"></i> <span id="tour-alert-text"></span>
            </div>
        </div>

        <!-- Doctor Section -->
        <div class="section-header">
            <i class="fa fa-user-md"></i> Doctor Visit Details
        </div>
        
        <div class="form-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Doctor</label>
                        <select class="form-control select-picker" name="doctor_id" id="doctor_id" data-live-search="true">
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" 
                                        data-speciality="{{ $doctor->speciality }}"
                                        data-area="{{ $doctor->area }}"
                                        data-station="{{ $doctor->exstation_id ?? $doctor->outstation_id }}">
                                    {{ $doctor->fullname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Speciality</label>
                        <input type="text" class="form-control bg-light" name="speciality" id="doctor_speciality" readonly>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label>MSL</label>
                        <input type="number" class="form-control" name="doctor_msl" value="0" min="0" max="10">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" class="form-control" name="doctor_area" id="doctor_area">
                    </div>
                </div>
            </div>
            
            <h6 class="mt-4 mb-3 text-primary border-bottom pb-2"><i class="fa fa-pills"></i> Products Promoted</h6>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th width="35%">Product</th>
                        <th width="15%">Samples Unit</th>
                        <th width="15%">POB</th>
                        <th width="35%">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select class="form-control" name="product1">
                                <option value="">Select Product 1</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="samples_unit1" value="0" min="0" placeholder="Units"></td>
                        <td><input type="number" class="form-control" name="pob_doctor1" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="doctor_remark1"></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control" name="product2">
                                <option value="">Select Product 2</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="samples_unit2" value="0" min="0" placeholder="Units"></td>
                        <td><input type="number" class="form-control" name="pob_doctor2" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="doctor_remark2"></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control" name="product3">
                                <option value="">Select Product 3</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="samples_unit3" value="0" min="0" placeholder="Units"></td>
                        <td><input type="number" class="form-control" name="pob_doctor3" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="doctor_remark3"></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input 1</label>
                        <input type="text" class="form-control" name="input1">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input 2</label>
                        <input type="text" class="form-control" name="input2">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>POB</label>
                        <input type="text" class="form-control" name="pob">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>General Remark</label>
                        <textarea class="form-control" name="doctor_general_remark" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chemist Section -->
        <div class="section-header">
            <i class="fa fa-flask"></i> Chemist Visit Details
        </div>
        
        <div class="form-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Chemist</label>
                        <select class="form-control select-picker" name="chemist_id" id="chemist_id" data-live-search="true">
                            <option value="">Select Chemist</option>
                            @foreach($chemists as $chemist)
                                <option value="{{ $chemist->id }}">{{ $chemist->shopname }} - {{ $chemist->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label>MSL</label>
                        <input type="number" class="form-control" name="chemist_msl" value="0" min="0" max="10">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" class="form-control" name="chemist_area">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Station</label>
                        <input type="text" class="form-control" name="chemist_station">
                    </div>
                </div>
            </div>
            
            <h6 class="mt-4 mb-3 text-success border-bottom pb-2"><i class="fa fa-prescription-bottle"></i> RCPA (Retail Chemist Prescription Audit)</h6>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th width="40%">Product</th>
                        <th width="15%">Amount</th>
                        <th width="45%">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select class="form-control" name="rcpa1">
                                <option value="">Select Product 1</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="chemist_pob_amount1" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="chemist_remark1"></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control" name="rcpa2">
                                <option value="">Select Product 2</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="chemist_pob_amount2" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="chemist_remark2"></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control" name="rcpa3">
                                <option value="">Select Product 3</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="chemist_pob_amount3" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="chemist_remark3"></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control" name="rcpa4">
                                <option value="">Select Product 4</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->name }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" class="form-control" name="chemist_pob_amount4" value="0" step="0.01"></td>
                        <td><input type="text" class="form-control" name="chemist_remark4"></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input 1</label>
                        <input type="text" class="form-control" name="chemist_input1">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input 2</label>
                        <input type="text" class="form-control" name="chemist_input2">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Input Remark</label>
                        <input type="text" class="form-control" name="chemist_input_remark">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>General Remark</label>
                        <textarea class="form-control" name="chemist_general_remark" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stockist Section -->
        <div class="section-header">
            <i class="fa fa-warehouse"></i> Stockist Visit Details
        </div>
        
        <div class="form-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Stockist</label>
                        <select class="form-control select-picker" name="stockist_id" id="stockist_id" data-live-search="true">
                            <option value="">Select Stockist</option>
                            @foreach($stockists as $stockist)
                                <option value="{{ $stockist->id }}">{{ $stockist->shopname }} - {{ $stockist->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <div class="form-group">
                        <label>MSL</label>
                        <input type="number" class="form-control" name="stockist_msl" value="0" min="0" max="10">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" class="form-control" name="stockist_area">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Station</label>
                        <input type="text" class="form-control" name="stockist_station">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>POB</label>
                        <input type="text" class="form-control" name="pob_stockist">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>POB Amount</label>
                        <input type="number" class="form-control" name="stockist_pob_amount" value="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Contact Mobile</label>
                        <input type="text" class="form-control" name="contact_person_mobile">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Proprietor</label>
                        <input type="text" class="form-control" name="proprietor">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Proprietor Mobile</label>
                        <input type="text" class="form-control" name="proprietor_mobile">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Stockist Remark</label>
                        <input type="text" class="form-control" name="stockist_remark">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>General Remark</label>
                        <textarea class="form-control" name="stockist_general_remark" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Save DCR Report</button>
            <a href="{{ route('dcr-management.index') }}" class="btn btn-secondary btn-lg ml-2"><i class="fa fa-times"></i> Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const isAdmin = {{ user()->hasRole('admin') ? 'true' : 'false' }};

$(document).ready(function() {
    
    // Initialize select pickers
    $('.select-picker').selectpicker();
    
    // Auto-fill doctor speciality and area
    $('#doctor_id').on('change', function() {
        const selected = $(this).find(':selected');
        $('#doctor_speciality').val(selected.data('speciality') || '');
        $('#doctor_area').val(selected.data('area') || '');
    });
    
    // Populate stations when HQ changes (for admin)
    $('#headquarter_id').on('changed.bs.select', function() {
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
                    $('#tour-alert-text').html(`According to Tour Plan <strong>${tour.station || tour.headquarter}</strong> is Approved`);
                    $('#tour-alert').slideDown();
                    
                    // Pre-fill station if available (single select now, take first value)
                    if (tour.station) {
                        const stationValue = tour.station.split(',')[0].trim(); // Take first station if comma-separated
                        $('#station').val(stationValue);
                        $('#station').selectpicker('refresh');
                    }
                } else {
                    $('#tour-alert').slideUp();
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
@endpush

