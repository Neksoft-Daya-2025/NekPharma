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

    /* Bootstrap .row is flex; default stretch makes all columns match tallest — avoid with long select lists */
    .doctor-visit-row .card-body > .row:first-of-type,
    .chemist-visit-row .card-body > .row:first-of-type,
    .stockist-visit-row .card-body > .row:first-of-type {
        align-items: flex-start;
    }

    /*
     * bootstrap-select 1.13.x only remaps .open -> .show when Bootstrap major === '4'.
     * On Bootstrap 5 (major 5), menus can stay in-flow and stretch flex columns to ~full options height.
     * Force overlay behavior for visit pickers only (scoped to DCR customer containers).
     */
    #doctor-visits-container .bootstrap-select,
    #chemist-visits-container .bootstrap-select,
    #stockist-visits-container .bootstrap-select {
        position: relative !important;
        width: 100% !important;
        display: block !important;
    }
    #doctor-visits-container .bootstrap-select > .dropdown-menu,
    #chemist-visits-container .bootstrap-select > .dropdown-menu,
    #stockist-visits-container .bootstrap-select > .dropdown-menu {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        right: auto !important;
        transform: none !important;
        float: none !important;
        width: 100% !important;
        min-width: 100% !important;
        max-height: min(70vh, 400px) !important;
        overflow-y: auto !important;
        z-index: 1065 !important;
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
        <div class="d-flex flex-wrap justify-content-between align-items-start">
            <div class="flex-grow-1 pr-3">
                <h2 class="mb-0"><i class="fa fa-clipboard-list"></i> {{ !empty($editingDcr) ? __('app.edit') . ' DCR' : 'Submit Daily Call Report (DCR)' }}</h2>
                <p class="mb-0 mt-2">Record your daily field activities and customer visits</p>
            </div>
            @if(empty($editingDcr) && !empty($dcrDraftResumeInfo['has_draft']))
                <div class="text-right ml-auto mt-2 mt-md-0" style="max-width: 280px;">
                    @if(!empty($dcrDraftResumeInfo['complete']))
                        <span class="badge badge-light text-dark border"><i class="fa fa-file-alt"></i> Draft</span>
                        @if(!empty($dcrDraftResumeInfo['report_date']))
                            <div class="small mt-1 opacity-95">{{ \Carbon\Carbon::parse($dcrDraftResumeInfo['report_date'])->format('d M Y') }}</div>
                        @endif
                        <a href="#dcr-form" class="btn btn-sm btn-light mt-2 font-weight-bold"><i class="fa fa-edit"></i> Edit draft</a>
                    @else
                        <span class="badge badge-warning"><i class="fa fa-exclamation-triangle"></i> Incomplete draft</span>
                        <p class="small mb-0 mt-2">Set Report date, Headquarter, Work type, and Submit to — then use Save DCR draft.</p>
                        <a href="#dcr-form" class="btn btn-sm btn-light mt-2 font-weight-bold"><i class="fa fa-arrow-down"></i> Go to form</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
        
    <form action="{{ !empty($editingDcr) ? route('dcr-management.update', $editingDcr->id) : route('dcr-management.store') }}" method="POST" id="dcr-form" novalidate>
            @csrf
            @if(!empty($editingDcr))
                @method('PUT')
            @endif
            @if(!empty($canAddForOthers) && $employeesForDropdown->isNotEmpty())
            <input type="hidden" name="user_id" id="dcr_user_id" value="">
            <div class="form-section mb-3">
                <h5 class="mb-3"><i class="fa fa-user-plus text-primary"></i> Add DCR for</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Employee</label>
                            <select class="form-control select-picker" id="dcr_employee_id" data-live-search="true" data-html="true">
                                @if(!empty($loggedInEmployeeId))
                                    <option value="" data-content="<span class='font-weight-bold'>{{ e($loggedInEmployeeId) }}</span> - Self (logged-in user)">{{ $loggedInEmployeeId }} - Self (logged-in user)</option>
                                @else
                                    <option value="">Self (logged-in user)</option>
                                @endif
                                @foreach($employeesForDropdown as $emp)
                                    @if(!empty($emp['employee_id']))
                                        <option value="{{ $emp['id'] }}" data-headquarter="{{ $emp['headquarter_id'] ?? '' }}" data-content="<span class='font-weight-bold'>{{ e($emp['employee_id']) }}</span> - {{ e($emp['name']) }} ({{ e($emp['designation']) }})">{{ $emp['employee_id'] }} - {{ $emp['name'] }} ({{ $emp['designation'] }})</option>
                                    @else
                                        <option value="{{ $emp['id'] }}" data-headquarter="{{ $emp['headquarter_id'] ?? '' }}">{{ $emp['name'] }} ({{ $emp['designation'] }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Select an employee to add DCR on their behalf; leave as "Self" for your own DCR.</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
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
                        @if(user()->hasAdminLikeAccess())
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
                        @elseif(isset($headquarters) && $headquarters->isNotEmpty() && ($headquarters->count() > 1 || ($showHqDropdownForPharmaRoles ?? false)))
                            {{-- ABM/RBM/ZM with multiple mapped HQs: show dropdown --}}
                            <select class="form-control select-picker" name="headquarter_id" id="headquarter_id" data-live-search="true" required>
                                <option value="">-- Select HeadQuarter --</option>
                                @foreach($headquarters as $hq)
                                    <option value="{{ $hq->id }}" {{ $userHeadquarter == $hq->id ? 'selected' : '' }}>
                                        {{ $hq->name }}
                                        @if($hq->area) ({{ $hq->area->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select HQ for this DCR report (your mapped headquarters)</small>
                        @elseif($userHeadquarter)
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
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Work Type <span class="text-danger">*</span></label>
                        <select class="form-control select-picker" name="work_status" id="work_status" data-live-search="true" required>
                            <option value="">Select Work Type</option>
                            @foreach(\App\Models\TourWorkStatus::where('is_active', true)->orderBy('name')->get() as $status)
                                <option value="{{ $status->name }}" {{ (!empty($editingDcr) && ($editingDcr->work_status ?? '') === $status->name) || (!empty($draftPayload) && ($draftPayload['work_status'] ?? '') === $status->name) ? 'selected' : '' }}>{{ $status->name }}</option>
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
                            @if(!user()->hasAdminLikeAccess())
                                {{-- Non-admin (ABM/Employee): Show all accessible headquarters and their stations --}}
                                @foreach($headquarters as $hq)
                                    <option value="{{ $hq->name }}" {{ !empty($editingDcr) && ($editingDcr->station ?? '') === $hq->name ? 'selected' : '' }}>{{ $hq->name }} (Headquarter)</option>
                                    @if($hq->exstations)
                                        @foreach($hq->exstations as $exstation)
                                            <option value="{{ $exstation->name }}" {{ !empty($editingDcr) && ($editingDcr->station ?? '') === $exstation->name ? 'selected' : '' }}>{{ $exstation->name }} (Ex-Station)</option>
                                        @endforeach
                                    @endif
                                    @if($hq->outstations)
                                        @foreach($hq->outstations as $outstation)
                                            <option value="{{ $outstation->name }}" {{ !empty($editingDcr) && ($editingDcr->station ?? '') === $outstation->name ? 'selected' : '' }}>{{ $outstation->name }} (Out-Station)</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            @else
                                {{-- Admin: Show stations only after selecting HQ (handled by JavaScript) --}}
                            @endif
                        </select>
                        <small class="text-muted">Select station (includes HQ)</small>
                    </div>
                </div>
                
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Work With</label>
                        @php
                            $dcrEditWorkWith = [];
                            if (!empty($editingDcr) && !empty($editingDcr->work_with)) {
                                $dcrEditWorkWith = array_filter(array_map('trim', explode(',', $editingDcr->work_with)));
                            } elseif (!empty($draftPayload) && !empty($draftPayload['work_with'])) {
                                $dcrEditWorkWith = $draftPayload['work_with'];
                            }
                        @endphp
                        <select class="form-control select-picker" name="work_with[]" id="work_with" data-live-search="true" multiple data-actions-box="true" data-select-all-text="Select All" data-deselect-all-text="Deselect All" data-selected-text-format="count > 3" data-count-selected-text="{0} selected">
                            @foreach($workedWithDesignations ?? [] as $designation)
                                <option value="{{ $designation }}" {{ in_array($designation, $dcrEditWorkWith, true) ? 'selected' : '' }}>{{ $designation }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select multiple designations (hierarchy names) to work with</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Submit To <span class="text-danger">*</span></label>
                        <select class="form-control select-picker" name="submitted_to" id="submitted_to" data-live-search="true" required>
                            <option value="">-- Select Manager --</option>
                            @if($reportingManagerId && $managers->isNotEmpty())
                                @php
                                    $reportingManager = $managers->first();
                                @endphp
                                @if($reportingManager)
                                    <option value="{{ $reportingManager->id }}" selected>
                                        {{ $reportingManager->name }}
                                        @if($reportingManager->employeeDetail && $reportingManager->employeeDetail->designation)
                                            ({{ $reportingManager->employeeDetail->designation->name }})
                                        @endif
                                    </option>
                                @endif
                            @endif
                        </select>
                        <small class="text-muted">
                            @if($reportingManagerId && $managers->isNotEmpty())
                                <span class="text-success"><i class="fa fa-check-circle"></i> Your reporting manager is pre-selected</span>
                            @else
                                <span class="text-warning"><i class="fa fa-exclamation-triangle"></i> No reporting manager assigned in HR. Please contact admin.</span>
                            @endif
                        </small>
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
                                  placeholder="Enter details about your day (meeting, conference, leave, etc.)">{{ !empty($editingDcr) ? ($editingDcr->remark ?? '') : (!empty($draftPayload) ? ($draftPayload['remark'] ?? '') : '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field Work Sections (Hidden by default) -->
        <div id="field-work-sections" style="display: none;">
        <p class="text-muted small mb-3" id="dcr-station-scope-hint">
            <i class="fa fa-info-circle"></i> Doctors, chemists, and stockists load for the <strong>selected station</strong> only. Choose Headquarter and Station before adding visits.
        </p>
        
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

        @if(empty($editingDcr))
        <!-- Always visible: draft workflow (same row idea as header; users scroll here first) -->
        <div class="card border shadow-sm mb-3 mt-4" id="dcr-draft-footer-hint">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start">
                    <div class="pr-3 mb-2 mb-md-0">
                        <strong class="d-block text-dark"><i class="fa fa-layer-group text-primary"></i> Draft &amp; submit</strong>
                        <span class="text-muted small">Close Day stays off until you <strong>Save DCR draft</strong> or <strong>Save visit</strong> (field work). You can edit your draft anytime after that.</span>
                    </div>
                    <div class="text-left text-md-right ml-md-auto" id="dcr-draft-footer-status">
                        @if(!empty($dcrDraftResumeInfo['has_draft']))
                            @if(!empty($dcrDraftResumeInfo['complete']))
                                <span class="badge badge-success align-middle">Draft saved</span>
                                @if(!empty($dcrDraftResumeInfo['report_date']))
                                    <span class="text-muted small ml-1 align-middle">{{ \Carbon\Carbon::parse($dcrDraftResumeInfo['report_date'])->format('d M Y') }}</span>
                                @endif
                                <a href="#dcr-form" class="btn btn-sm btn-outline-primary ml-0 ml-md-2 mt-2 mt-md-0 align-middle"><i class="fa fa-edit"></i> Edit draft</a>
                            @else
                                <span class="badge badge-warning align-middle">Incomplete draft</span>
                                <a href="#dcr-form" class="btn btn-sm btn-outline-secondary ml-0 ml-md-2 mt-2 mt-md-0 align-middle"><i class="fa fa-arrow-down"></i> Complete header</a>
                            @endif
                        @else
                            <span class="badge badge-secondary align-middle">No draft saved yet</span>
                            <span class="text-muted small ml-1 align-middle">Use Save DCR draft when the header is filled</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Submit Buttons (SRS 3.2.5: Close Day submits DCR and marks attendance) -->
        <div class="text-center mt-4">
            @if(!empty($editingDcr))
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Close Day & Submit DCR</button>
                <a href="{{ route('dcr-management.index') }}" class="btn btn-secondary btn-lg ml-2"><i class="fa fa-times"></i> Cancel</a>
                <p class="text-muted small mt-2 mb-0">Submitting closes your day for this date and marks attendance.</p>
            @else
                <button type="button" id="dcr-save-draft-btn" class="btn btn-outline-primary btn-lg mr-2 mb-2">
                    <i class="fa fa-database"></i> Save DCR draft
                </button>
                <button type="submit" id="dcr-submit-close-day" class="btn btn-primary btn-lg mb-2" disabled title="Save draft or save at least one visit first">
                    <i class="fa fa-check-circle"></i> Close Day & Submit DCR
                </button>
                <a href="{{ route('dcr-management.index') }}" class="btn btn-secondary btn-lg ml-2 mb-2"><i class="fa fa-times"></i> Cancel</a>
                <p class="text-muted small mt-2 mb-0">Use <strong>Save DCR draft</strong> (or <strong>Save visit</strong> on each card) before submitting. Submitting closes your day for this date and marks attendance.</p>
            @endif
        </div>
    </form>

    <!-- Quick add doctor (DCR station context) -->
    <div class="modal fade" id="dcr-quick-add-doctor-modal" tabindex="-1" role="dialog" aria-labelledby="dcrQuickAddDoctorLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dcrQuickAddDoctorLabel"><i class="fa fa-user-plus"></i> Quick add doctor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="dcr_quick_doctor_visit_id" value="">
                    <p class="text-muted small">This doctor is created for the current DCR headquarter and station.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Full name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="dcr_qd_fullname" required maxlength="255" placeholder="Dr. Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" class="form-control" id="dcr_qd_mobile" maxlength="20">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" id="dcr_qd_email" maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Speciality</label>
                                <input type="text" class="form-control" id="dcr_qd_speciality" maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" class="form-control" id="dcr_qd_qualification" maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Address</label>
                                <textarea class="form-control" id="dcr_qd_address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="dcr-quick-add-doctor-save"><i class="fa fa-save"></i> Save doctor</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $fieldWorkTypesConfig = config('dcr.field_work_types', ['Field Work', 'Working Day', 'Working Days']);
@endphp
<script>
const isAdmin = {{ user()->hasAdminLikeAccess() ? 'true' : 'false' }};
const ALL_HEADQUARTERS = @json($headquarters);
// Work types that enable Doctor/Chemist/Stockist (SRS 3.2.5: Working Day / Field Work / Working Days)
const fieldWorkTypes = @json($fieldWorkTypesConfig);
window.dcrEditMode = @json(!empty($editingDcr));
window.dcrDraftPayload = @json($draftPayload ?? null);
window.dcrDraftLoaded = !!(window.dcrDraftPayload && window.dcrDraftPayload.dcr_id);
window.dcrPendingDraftHydration = !!window.dcrDraftLoaded;

function dcrEnableCloseDaySubmit() {
    if ($('#dcr-submit-close-day').length) {
        $('#dcr-submit-close-day').prop('disabled', false);
    }
}
function dcrUpdateFooterDraftSaved() {
    var $st = $('#dcr-draft-footer-status');
    if (!$st.length) return;
    var rd = $('#report_date').val() || '';
    var dateHtml = rd ? ' <span class="text-muted small ml-1 align-middle">' + $('<span>').text(rd).html() + '</span>' : '';
    $st.html(
        '<span class="badge badge-success align-middle">Draft saved</span>' + dateHtml +
        ' <a href="#dcr-form" class="btn btn-sm btn-outline-primary ml-0 ml-md-2 mt-2 mt-md-0 align-middle"><i class="fa fa-edit"></i> Edit draft</a>'
    );
}
@if(empty($editingDcr))
if (window.dcrDraftLoaded) {
    $(function () { dcrEnableCloseDaySubmit(); });
}
@endif

function isFieldWorkSelected() {
    const el = $('#work_status');
    const selectedStatus = el.selectpicker ? el.selectpicker('val') : el.val();
    return fieldWorkTypes.includes(selectedStatus);
}

/** Append dropdown to body — avoids huge column height when .row uses flex stretch + long doctor lists */
const dcrVisitSelectpickerOpts = { container: 'body' };

function getHQById(hqId) {
    if (!hqId) return null;
    return ALL_HEADQUARTERS.find(h => Number(h.id) === Number(hqId)) || null;
}

/** Merge approved tour HQ (and ex/out stations) so MRs can pick that territory even when it is outside their profile HQ. */
function dcrMergeTourHeadquarterBundle(bundle) {
    if (!bundle || bundle.id == null || bundle.id === '') return;
    const id = Number(bundle.id);
    if (!id) return;
    if (ALL_HEADQUARTERS.some(h => Number(h.id) === id)) return;
    ALL_HEADQUARTERS.push({
        id: bundle.id,
        name: bundle.name || '',
        area: bundle.area || null,
        exstations: bundle.exstations || [],
        outstations: bundle.outstations || [],
    });
}

$(document).ready(function() {
    $('#dcr-save-draft-btn').on('click', function () {
        const $form = $('#dcr-form');
        const reportDate = $('#report_date').val();
        let headquarterId = $('#headquarter_id').val();
        if ($('#headquarter_id').length && $('#headquarter_id').is('select') && $('#headquarter_id').selectpicker) {
            headquarterId = $('#headquarter_id').selectpicker('val') || headquarterId;
        }
        const workStatus = $('#work_status').selectpicker ? $('#work_status').selectpicker('val') : $('#work_status').val();
        const submittedTo = $('#submitted_to').selectpicker ? $('#submitted_to').selectpicker('val') : $('#submitted_to').val();
        if (!reportDate || !headquarterId || !workStatus || !submittedTo) {
            Swal.fire({ icon: 'warning', text: 'Please fill Report Date, Headquarter, Work Type and Submit To before saving draft.' });
            return;
        }
        $.easyAjax({
            url: "{{ route('dcr.save-draft') }}",
            type: 'POST',
            container: '#dcr-form',
            disableButton: true,
            buttonSelector: '#dcr-save-draft-btn',
            data: $form.serialize(),
            success: function (response) {
                if (response && response.status === 'success') {
                    dcrEnableCloseDaySubmit();
                    dcrUpdateFooterDraftSaved();
                }
            }
        });
    });

    // Initialize select pickers
    $('.select-picker').selectpicker();
    
    // ======================================= sonu ==========================================================
    // Function to populate all accessible stations for non-admin users
    function populateAllAccessibleStations() {
        if (isAdmin) return; // Admin selects HQ first, then stations
        
        const $station = $('#station');
        $station.find('option:not(:first)').remove();
        
        // Populate stations from all accessible headquarters (already filtered by controller)
        ALL_HEADQUARTERS.forEach(hq => {
            $station.append(`<option value="${hq.name}">${hq.name} (Headquarter)</option>`);
            
            if (hq.exstations && hq.exstations.length > 0) {
                hq.exstations.forEach(station => {
                    $station.append(`<option value="${station.name}">${station.name} (Ex-Station)</option>`);
                });
            }
            
            if (hq.outstations && hq.outstations.length > 0) {
                hq.outstations.forEach(station => {
                    $station.append(`<option value="${station.name}">${station.name} (Out-Station)</option>`);
                });
            }
        });
        
        $station.selectpicker('refresh');
    }
    
    // Initialize stations based on user role
    if (isAdmin) {
        // Admin: populate stations when HQ is selected
        const initialHQ = $('#headquarter_id').val();
        if (initialHQ) {
            populateStationsByHQ(initialHQ);
        }
    } else {
        // Non-admin (ABM/Employee): populate all accessible stations immediately
        populateAllAccessibleStations();
    }

    let selectedStationType = null;
    let selectedStationId = null;

    function clearStationCustomerLists() {
        selectedStationType = null;
        selectedStationId = null;
        window.filteredDoctors = [];
        window.filteredChemists = [];
        window.filteredStockists = [];
        refreshDoctorDropdowns();
        refreshChemistDropdowns();
        refreshStockistDropdowns();
    }

    $('#station').on('changed.bs.select', function () {
        const rawVal = $(this).val();
        const selectedText = $(this).find(':selected').text().trim();

        if (!rawVal || !selectedText) {
            clearStationCustomerLists();
            return;
        }

        const hqId = $('#headquarter_id').val() || '{{ $userHeadquarter }}';
        const hq = getHQById(hqId);
        if (!hq) {
            clearStationCustomerLists();
            console.warn('HQ not found');
            return;
        }

        let matched = false;

        if (selectedText.includes('(Headquarter)')) {
            selectedStationType = 'headquarter';
            selectedStationId = hq.id;
            matched = true;
        } else if (selectedText.includes('(Ex-Station)')) {
            const name = selectedText.replace('(Ex-Station)', '').trim();
            const ex = (hq.exstations || []).find(s => s.name === name);
            if (ex) {
                selectedStationType = 'exstation';
                selectedStationId = ex.id;
                matched = true;
            }
        } else if (selectedText.includes('(Out-Station)')) {
            const name = selectedText.replace('(Out-Station)', '').trim();
            const out = (hq.outstations || []).find(s => s.name === name);
            if (out) {
                selectedStationType = 'outstation';
                selectedStationId = out.id;
                matched = true;
            }
        }

        if (!matched) {
            clearStationCustomerLists();
            return;
        }

        console.log('Station Selected:', selectedStationType, selectedStationId);
        fetchFilteredLists();
    });

    
    function fetchFilteredLists() {
    if (!selectedStationType || !selectedStationId) {
        console.warn('Station not resolved yet');
        return;
    }

    const stationCustomerParams = {
        station_type: selectedStationType,
        station_id: selectedStationId,
        report_date: $('#report_date').val() || '',
    };
    const dcrEmpPick = $('#dcr_employee_id');
    if (dcrEmpPick.length && dcrEmpPick.val()) {
        stationCustomerParams.user_id = dcrEmpPick.val();
    }

    $.easyAjax({
        url: "{{ route('dcr.get-station-customers') }}",
        type: "GET",
        data: stationCustomerParams,
        success: function(res) {

        window.filteredDoctors   = res.doctors   || [];
        window.filteredChemists  = res.chemists  || [];
        window.filteredStockists = res.stockists || [];
    
        console.log('Doctors loaded:', window.filteredDoctors.length);

        if (isFieldWorkSelected()) {
            if (window.dcrPendingDraftHydration && window.dcrDraftPayload) {
                // Hydrate saved draft visits instead of empty placeholder rows
            } else if ($('#doctor-visits-container').children().length === 0) {
                addDoctorVisit();
            } else {
                refreshDoctorDropdowns();
            }
            if (window.dcrPendingDraftHydration && window.dcrDraftPayload) {
                // chemist/stockist handled in hydrateDraftVisitsFromPayload
            } else if ($('#chemist-visits-container').children().length === 0) {
                addChemistVisit();
            } else {
                refreshChemistDropdowns();
            }
            if (window.dcrPendingDraftHydration && window.dcrDraftPayload) {
                // stockist handled in hydrateDraftVisitsFromPayload
            } else if ($('#stockist-visits-container').children().length === 0) {
                addStockistVisit();
            } else {
                refreshStockistDropdowns();
            }
            if (window.dcrPendingDraftHydration && window.dcrDraftPayload) {
                hydrateDraftVisitsFromPayload();
                window.dcrPendingDraftHydration = false;
            }
        } else {
            refreshDoctorDropdowns();
            refreshChemistDropdowns();
            refreshStockistDropdowns();
        }
    }

    });
}

function mergeCustomerFromAll(partial, allList) {
    if (!partial || !partial.id) return partial;
    const full = (allList || []).find(function (x) { return Number(x.id) === Number(partial.id); });
    return full ? Object.assign({}, full, partial) : partial;
}

function escapeHtmlAttr(s) {
    if (s == null || s === '') return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;');
}

/** Doctor master MSL code for data-msl-number (not visit score msl). */
function doctorMslNumberAttr(d) {
    if (!d) return '';
    var v = d.msl_number != null && d.msl_number !== '' ? d.msl_number : d.msl;
    return v != null && v !== '' ? escapeHtmlAttr(v) : '';
}

function dcrPushDoctorToCatalog(doc) {
    if (!doc || !doc.id) return;
    window.dcrDoctorCatalog = window.dcrDoctorCatalog || [];
    if (!window.dcrDoctorCatalog.some(function (x) { return Number(x.id) === Number(doc.id); })) {
        window.dcrDoctorCatalog.push(doc);
    }
    window.filteredDoctors = window.filteredDoctors || [];
    if (!window.filteredDoctors.some(function (x) { return Number(x.id) === Number(doc.id); })) {
        window.filteredDoctors.push(doc);
    }
}

function refreshDoctorDropdowns() {
    $('.doctor-select').each(function () {
        const $select = $(this);
        let prevVal = '';
        try {
            prevVal = $select.selectpicker('val');
        } catch (e) {
            prevVal = $select.val();
        }
        if (prevVal === null || prevVal === undefined) {
            prevVal = '';
        }

        // Keep the chosen doctor in the catalog before the select is destroyed (filtered list may omit them later)
        if (prevVal) {
            const $prevOpt = $select.find('option').filter(function () {
                return String($(this).val()) === String(prevVal);
            }).first();
            if ($prevOpt.length) {
                const minimal = {
                    id: prevVal,
                    fullname: ($prevOpt.text() || '').trim(),
                    speciality: $prevOpt.attr('data-speciality') || '',
                    area: $prevOpt.attr('data-area') || '',
                };
                dcrPushDoctorToCatalog(mergeCustomerFromAll(minimal, window.dcrDoctorCatalog || []));
            }
        }

        $select.selectpicker('destroy');

        let options = '<option value="">-- Select Doctor --</option>';
        const rawList = window.filteredDoctors || [];
        const idSet = new Set();
        rawList.forEach(function (d) {
            if (!d || !d.id) return;
            const merged = mergeCustomerFromAll(d, window.dcrDoctorCatalog || []);
            idSet.add(String(merged.id));
            const areaName = merged.area ? (merged.area.name || merged.area) : (merged.area_name || '');
            const hqName = merged.headquarter ? (merged.headquarter.name || merged.headquarter) : '';
            const exstationName = merged.exstation ? (merged.exstation.name || merged.exstation) : '';
            const outstationName = merged.outstation ? (merged.outstation.name || merged.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            options += '<option value="' + merged.id + '"' +
                ' data-speciality="' + (merged.speciality || '') + '"' +
                ' data-area="' + (areaName || '') + '"' +
                ' data-station-name="' + (stationName || '') + '"' +
                ' data-msl-number="' + doctorMslNumberAttr(merged) + '">' +
                (merged.fullname || 'Unknown') +
                '</option>';
        });

        const prevStr = String(prevVal);
        if (prevStr && !idSet.has(prevStr)) {
            const cat = window.dcrDoctorCatalog || [];
            const found = cat.find(function (x) { return x && String(x.id) === prevStr; });
            if (found) {
                const merged = mergeCustomerFromAll(found, cat);
                idSet.add(String(merged.id));
                const areaName = merged.area ? (merged.area.name || merged.area) : (merged.area_name || '');
                const hqName = merged.headquarter ? (merged.headquarter.name || merged.headquarter) : '';
                const exstationName = merged.exstation ? (merged.exstation.name || merged.exstation) : '';
                const outstationName = merged.outstation ? (merged.outstation.name || merged.outstation) : '';
                const stationName = exstationName || outstationName || hqName;
                options += '<option value="' + merged.id + '"' +
                    ' data-speciality="' + (merged.speciality || '') + '"' +
                    ' data-area="' + (areaName || '') + '"' +
                    ' data-station-name="' + (stationName || '') + '"' +
                    ' data-msl-number="' + doctorMslNumberAttr(merged) + '">' +
                    (merged.fullname || 'Unknown') +
                    '</option>';
            }
        }

        $select.html(options);
        $select.selectpicker(dcrVisitSelectpickerOpts);

        const visitRow = $select.closest('.doctor-visit-row');
        if (prevStr && idSet.has(prevStr)) {
            $select.selectpicker('val', prevVal);
            const $opt = $select.find('option').filter(function () {
                return String($(this).val()) === prevStr;
            }).first();
            if ($opt.length) {
                visitRow.find('.doctor-speciality').val($opt.attr('data-speciality') || '');
                visitRow.find('.doctor-station').val($opt.attr('data-station-name') || '');
                visitRow.find('.doctor-msl-number').val($opt.attr('data-msl-number') || '');
            }
        } else if (prevStr) {
            $select.selectpicker('val', '');
            visitRow.find('.doctor-speciality').val('');
            visitRow.find('.doctor-station').val('');
            visitRow.find('.doctor-msl-number').val('');
            visitRow.find('.doctor-msl-score').val(0);
        }
    });
}

function refreshChemistDropdowns() {
    $('.chemist-select').each(function () {
        const $select = $(this);
        let prevVal = '';
        try {
            prevVal = $select.selectpicker('val');
        } catch (e) {
            prevVal = $select.val();
        }
        if (prevVal === null || prevVal === undefined) {
            prevVal = '';
        }

        $select.selectpicker('destroy');

        let options = '<option value="">-- Select Chemist --</option>';
        const rawList = window.filteredChemists || [];
        const idSet = new Set();
        rawList.forEach(function (c) {
            if (!c || !c.id) return;
            const merged = mergeCustomerFromAll(c, window.dcrChemistCatalog || []);
            idSet.add(String(merged.id));
            const hqName = merged.headquarter ? (merged.headquarter.name || merged.headquarter) : '';
            const exstationName = merged.exstation ? (merged.exstation.name || merged.exstation) : '';
            const outstationName = merged.outstation ? (merged.outstation.name || merged.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            options += '<option value="' + merged.id + '"' +
                ' data-station-name="' + (stationName || '') + '">' +
                (merged.shopname || 'Unknown') +
                '</option>';
        });

        $select.html(options);
        $select.selectpicker(dcrVisitSelectpickerOpts);

        const visitRow = $select.closest('.chemist-visit-row');
        const prevStr = String(prevVal);
        if (prevStr && idSet.has(prevStr)) {
            $select.selectpicker('val', prevVal);
            $select.trigger('changed.bs.select');
        } else if (prevStr) {
            $select.selectpicker('val', '');
            visitRow.find('.chemist-station-name').val('');
        }
    });
}

function refreshStockistDropdowns() {
    $('.stockist-select').each(function () {
        const $select = $(this);
        let prevVal = '';
        try {
            prevVal = $select.selectpicker('val');
        } catch (e) {
            prevVal = $select.val();
        }
        if (prevVal === null || prevVal === undefined) {
            prevVal = '';
        }

        $select.selectpicker('destroy');

        let options = '<option value="">-- Select Stockist --</option>';
        const rawList = window.filteredStockists || [];
        const idSet = new Set();
        rawList.forEach(function (s) {
            if (!s || !s.id) return;
            const merged = mergeCustomerFromAll(s, window.dcrStockistCatalog || []);
            idSet.add(String(merged.id));
            const hqName = merged.headquarter ? (merged.headquarter.name || merged.headquarter) : '';
            const exstationName = merged.exstation ? (merged.exstation.name || merged.exstation) : '';
            const outstationName = merged.outstation ? (merged.outstation.name || merged.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            options += '<option value="' + merged.id + '"' +
                ' data-station-name="' + (stationName || '') + '">' +
                (merged.shopname || 'Unknown') +
                '</option>';
        });

        $select.html(options);
        $select.selectpicker(dcrVisitSelectpickerOpts);

        const visitRow = $select.closest('.stockist-visit-row');
        const prevStr = String(prevVal);
        if (prevStr && idSet.has(prevStr)) {
            $select.selectpicker('val', prevVal);
            $select.trigger('changed.bs.select');
        } else if (prevStr) {
            $select.selectpicker('val', '');
            visitRow.find('.stockist-station-name').val('');
        }
    });
}






    // =======================================================================================================
    
    // Toggle sections based on work type
    function toggleSectionsByWorkStatus() {
        const selectedStatus = $('#work_status').val();
        
        console.log('Work Type changed:', selectedStatus);
        
        // Show field sections for field-work types (Field Work / Working Day / Working Days)
        if (fieldWorkTypes.includes(selectedStatus)) {
            // Show doctor, chemist, stockist sections
            $('#field-work-sections').slideDown();
            $('#non-field-work-section').slideUp();
            $('#non_field_remark').prop('required', false);
            
            // Remove required from non-field work section (it's hidden)
            $('#non-field-work-section').find('input[required], textarea[required]').prop('required', false);
            
            // Keep required on field work fields (they're visible)
            // Note: Required is set in HTML, so we don't need to add it here

            if (selectedStationType && selectedStationId) {
                if ($('#doctor-visits-container').children().length === 0) {
                    addDoctorVisit();
                }
                if ($('#chemist-visits-container').children().length === 0) {
                    addChemistVisit();
                }
                if ($('#stockist-visits-container').children().length === 0) {
                    addStockistVisit();
                }
            }
        } else if (selectedStatus) {
            // Show only remark section for non-field work
            $('#field-work-sections').slideUp();
            $('#non-field-work-section').slideDown();
            $('#non_field_remark').prop('required', true);
            
            // Remove required from all field work fields (they're hidden)
            $('#field-work-sections').find('select[required], input[required]').prop('required', false);
        } else {
            // Nothing selected, hide all
            $('#field-work-sections').slideUp();
            $('#non-field-work-section').slideUp();
            $('#non_field_remark').prop('required', false);
            
            // Remove required from all fields when nothing is selected
            $('#field-work-sections').find('select[required], input[required]').prop('required', false);
            $('#non-field-work-section').find('input[required], textarea[required]').prop('required', false);
        }
    }
    
    // Watch for work status changes
    $('#work_status').on('change', function() {
        toggleSectionsByWorkStatus();
    });
    
    // Doctor visits management
    let doctorVisitCounter = 0;
    const allDoctors = @json($doctors ?? []);
    const allChemists = @json($chemists ?? []);
    const allStockists = @json($stockists ?? []);
    const allProducts = @json($products ?? []);
    window.dcrDoctorCatalog = allDoctors.slice ? allDoctors.slice() : [];
    window.dcrChemistCatalog = allChemists.slice ? allChemists.slice() : [];
    window.dcrStockistCatalog = allStockists.slice ? allStockists.slice() : [];
    
    window.filteredDoctors = [];
    window.filteredChemists = [];
    window.filteredStockists = [];
    
    console.log('DCR: customer lists load after station is selected (embedded catalog size:', allDoctors.length, ')');
    
    $('#add-doctor-btn').on('click', function() {
        if (!selectedStationType || !selectedStationId) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter and Station first.' });
            return;
        }
        addDoctorVisit();
    });
    
    function hydrateDraftVisitsFromPayload() {
        if (!window.dcrDraftPayload || !window.dcrDraftLoaded) {
            return;
        }
        $('#doctor-visits-container').empty();
        $('#chemist-visits-container').empty();
        $('#stockist-visits-container').empty();
        doctorVisitCounter = 0;
        chemistVisitCounter = 0;
        stockistVisitCounter = 0;
        (window.dcrDraftPayload.doctor_visits || []).forEach(function (row) { addDoctorVisit(row); });
        (window.dcrDraftPayload.chemist_visits || []).forEach(function (row) { addChemistVisit(row); });
        (window.dcrDraftPayload.stockist_visits || []).forEach(function (row) { addStockistVisit(row); });
    }

    function addDoctorVisit(draftRow) {
        doctorVisitCounter++;
        const visitId = doctorVisitCounter;
        
        const doctorsList = window.filteredDoctors || [];
        let doctorOptions = '<option value="">-- Select Doctor --</option>';
        if (doctorsList.length === 0) {
            doctorOptions += '<option value="" disabled>No doctors for this station yet — use Quick add or check master data</option>';
        }
        doctorsList.forEach(function(raw) {
            if (!raw || !raw.id) return;
            const d = mergeCustomerFromAll(raw, window.dcrDoctorCatalog || []);
            const areaName = d.area ? (d.area.name || d.area) : (d.area_name || '');
            const hqName = d.headquarter ? (d.headquarter.name || d.headquarter) : '';
            const exstationName = d.exstation ? (d.exstation.name || d.exstation) : '';
            const outstationName = d.outstation ? (d.outstation.name || d.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            
            doctorOptions += '<option value="' + d.id + '"' +
                ' data-speciality="' + (d.speciality || '') + '"' +
                ' data-area="' + (areaName || '') + '"' +
                ' data-station-name="' + (stationName || '') + '"' +
                ' data-msl-number="' + doctorMslNumberAttr(d) + '">' +
                (d.fullname || 'Unknown') +
            '</option>';
        });
        
        // Build product rows HTML
        const productRowsHtml = [1, 2, 3].map(function(i) {
            const productOptions = (allProducts || []).map(function(p) {
                return '<option value="' + (p.name || p.id || '') + '">' + (p.name || 'Unknown') + '</option>';
            }).join('');
            return '<tr>' +
                '<td>' +
                    '<select class="form-control" name="doctors[' + visitId + '][product' + i + ']">' +
                        '<option value="">-- Select Product ' + i + ' --</option>' +
                        productOptions +
                    '</select>' +
                '</td>' +
                '<td><input type="number" class="form-control text-center" name="doctors[' + visitId + '][samples_unit' + i + ']" value="0" min="0" placeholder="0"></td>' +
                '<td><input type="number" class="form-control text-center" name="doctors[' + visitId + '][pob' + i + ']" value="0" step="0.01" placeholder="0.00"></td>' +
                '<td><input type="text" class="form-control" name="doctors[' + visitId + '][remark' + i + ']" placeholder="Enter remark..."></td>' +
            '</tr>';
        }).join('');
        
        const doctorHtml = '<div class="card shadow-sm mb-4 doctor-visit-row" data-visit-id="' + visitId + '" data-server-visit-id="" data-dcr-id="" style="border-left: 4px solid #8bab4c;">' +
            '<div class="card-header bg-light d-flex justify-content-between align-items-center py-2">' +
                '<h6 class="mb-0">' +
                    '<span class="badge badge-primary mr-2">#' + visitId + '</span>' +
                    '<i class="fa fa-user-md text-primary"></i> Doctor Visit' +
                '</h6>' +
                '<div class="d-flex align-items-center flex-wrap">' +
                    '<button type="button" class="btn btn-outline-primary btn-sm mr-2 mb-1 dcr-open-quick-add-doctor" data-visit-id="' + visitId + '">' +
                        '<i class="fa fa-user-plus"></i> Quick add' +
                    '</button>' +
                    '<button type="button" class="btn btn-success btn-sm mr-2 mb-1 dcr-save-visit" data-visit-type="doctor" title="Save this visit to draft (GPS required when master has coordinates)">' +
                        '<i class="fa fa-save"></i> Save visit' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger btn-sm mb-1 remove-doctor-visit" data-visit-id="' + visitId + '">' +
                        '<i class="fa fa-trash"></i> Remove' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Dr. <span class="text-danger">*</span></label>' +
                            '<select class="form-control select-picker doctor-select" name="doctors[' + visitId + '][doctor_id]" data-visit-id="' + visitId + '" data-container="body" data-live-search="true" required>' +
                                doctorOptions +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Speciality</label>' +
                            '<input type="text" class="form-control bg-light doctor-speciality" name="doctors[' + visitId + '][speciality]" readonly>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-3">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Station</label>' +
                            '<input type="text" class="form-control bg-light doctor-station" name="doctors[' + visitId + '][station_info]" readonly>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="row">' +
                    '<div class="col-md-2">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">MSL Number</label>' +
                            '<input type="text" class="form-control bg-light doctor-msl-number" value="" readonly placeholder="From doctor record">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Visit MSL <span class="text-muted">(0–10)</span></label>' +
                            '<input type="number" class="form-control doctor-msl-score" name="doctors[' + visitId + '][msl]" value="0" min="0" max="10" placeholder="0-10">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">GPS Location</label>' +
                            '<div class="input-group">' +
                                '<input type="hidden" name="doctors[' + visitId + '][latitude]" class="visit-lat" value="">' +
                                '<input type="hidden" name="doctors[' + visitId + '][longitude]" class="visit-lon" value="">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm get-location" title="Use current location for 100m check"><i class="fa fa-map-marker-alt"></i> Current location</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<hr class="my-3">' +
                '<h6 class="text-primary mb-3"><i class="fa fa-pills"></i> Products Promoted</h6>' +
                '<div class="table-responsive">' +
                    '<table class="table table-bordered table-hover">' +
                        '<thead class="thead-light">' +
                            '<tr>' +
                                '<th width="30%">Product</th>' +
                                '<th width="15%" class="text-center">Samples Unit</th>' +
                                '<th width="15%" class="text-center">POB</th>' +
                                '<th width="40%">Remark</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                            productRowsHtml +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
                '<div class="row mt-3">' +
                    '<div class="col-md-12">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">General Remark</label>' +
                            '<textarea class="form-control" name="doctors[' + visitId + '][general_remark]" rows="2" placeholder="Overall remarks for this doctor visit..."></textarea>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('#doctor-visits-container').append(doctorHtml);
        $('.doctor-select[data-visit-id="' + visitId + '"]').selectpicker(dcrVisitSelectpickerOpts);
        if (draftRow && draftRow.id && window.dcrDraftPayload) {
            const $card = $('#doctor-visits-container .doctor-visit-row[data-visit-id="' + visitId + '"]');
            $card.attr('data-server-visit-id', String(draftRow.id));
            $card.attr('data-dcr-id', String(window.dcrDraftPayload.dcr_id));
            $card.attr('data-auto-saved', '1');
            if (draftRow.doctor_id) {
                const $sel = $card.find('.doctor-select');
                if (!$sel.find('option[value="' + draftRow.doctor_id + '"]').length) {
                    const lab = (draftRow.doctor_label || ('Doctor #' + draftRow.doctor_id)).replace(/</g, '');
                    const sp = (draftRow.speciality || '').replace(/"/g, '&quot;');
                    $sel.append('<option value="' + draftRow.doctor_id + '" data-speciality="' + sp + '" data-area="" data-station-name="">' + lab + '</option>');
                }
                $sel.selectpicker('refresh');
                $sel.selectpicker('val', String(draftRow.doctor_id));
                const selOpt = $sel.find('option:selected');
                $card.find('.doctor-speciality').val(draftRow.speciality || selOpt.attr('data-speciality') || '');
                $card.find('.doctor-station').val(selOpt.attr('data-station-name') || '');
                $card.find('.doctor-msl-number').val(selOpt.attr('data-msl-number') || '');
            }
            $card.find('.doctor-msl-score').val(draftRow.msl != null ? draftRow.msl : 0);
            $card.find('.visit-lat').val(draftRow.latitude || '');
            $card.find('.visit-lon').val(draftRow.longitude || '');
            $card.find('textarea[name="doctors[' + visitId + '][general_remark]"]').val(draftRow.general_remark || '');
            for (let pi = 1; pi <= 3; pi++) {
                $card.find('select[name="doctors[' + visitId + '][product' + pi + ']"]').val(draftRow['product' + pi] || '');
                $card.find('input[name="doctors[' + visitId + '][samples_unit' + pi + ']"]').val(draftRow['samples_unit' + pi] != null ? draftRow['samples_unit' + pi] : 0);
                $card.find('input[name="doctors[' + visitId + '][pob' + pi + ']"]').val(draftRow['pob' + pi] != null ? draftRow['pob' + pi] : 0);
                $card.find('input[name="doctors[' + visitId + '][remark' + pi + ']"]').val(draftRow['remark' + pi] || '');
            }
        }
    }
    
    function dcrDeleteVisitRow($card, visitType, visitIdDom, onDone) {
        const sid = $card.attr('data-server-visit-id');
        const did = $card.attr('data-dcr-id');
        if (sid && did) {
            Swal.fire({
                title: 'Remove this saved visit?',
                text: 'This will delete the visit from your draft DCR.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                const delPayload = {
                    _token: '{{ csrf_token() }}',
                    visit_type: visitType,
                    visit_id: parseInt(sid, 10),
                    dcr_id: parseInt(did, 10)
                };
                const delUid = $('#dcr_user_id').length ? $('#dcr_user_id').val() : '';
                if (delUid) {
                    delPayload.user_id = delUid;
                }
                $.ajax({
                    url: "{{ route('dcr.destroy-visit') }}",
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(delPayload),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                }).done(function (res) {
                    if (res.status === 'success') {
                        $card.remove();
                        if (typeof onDone === 'function') onDone();
                        Swal.fire({ icon: 'success', text: res.message || 'Removed.', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    }
                }).fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Request failed';
                    Swal.fire({ icon: 'error', text: msg });
                });
            });
        } else {
            $card.remove();
            if (typeof onDone === 'function') onDone();
        }
    }

    $(document).on('click', '.remove-doctor-visit', function () {
        const $card = $(this).closest('.doctor-visit-row');
        dcrDeleteVisitRow($card, 'doctor', $(this).data('visit-id'));
    });

    $(document).on('changed.bs.select', '#doctor-visits-container .doctor-select', function () {
        const $sel = $(this);
        const id = $sel.val();
        const visitRow = $sel.closest('.doctor-visit-row');
        const selected = $sel.find('option:selected');
        if (id) {
            const rawList = window.filteredDoctors || [];
            let doctorObj = rawList.find(function (x) { return x && String(x.id) === String(id); });
            if (!doctorObj && window.dcrDoctorCatalog) {
                doctorObj = window.dcrDoctorCatalog.find(function (x) { return x && String(x.id) === String(id); });
            }
            if (doctorObj) {
                dcrPushDoctorToCatalog(doctorObj);
            }
        }
        visitRow.find('.doctor-speciality').val(selected.attr('data-speciality') || '');
        visitRow.find('.doctor-station').val(selected.attr('data-station-name') || '');
        visitRow.find('.doctor-msl-number').val(selected.attr('data-msl-number') || '');
        visitRow.find('.doctor-msl-score').val(0);
    });

    function dcrGetHeadquarterIdForAjax() {
        let hqId = $('#headquarter_id').val();
        if ($('#headquarter_id').selectpicker) {
            try {
                hqId = $('#headquarter_id').selectpicker('val') || hqId;
            } catch (e) { /* keep val() */ }
        }
        return hqId;
    }

    $(document).on('click', '.dcr-open-quick-add-doctor', function (e) {
        e.preventDefault();
        if (!selectedStationType || !selectedStationId) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter and Station first.' });
            return;
        }
        if (!dcrGetHeadquarterIdForAjax()) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter first.' });
            return;
        }
        $('#dcr_quick_doctor_visit_id').val($(this).data('visit-id'));
        $('#dcr_qd_fullname').val('');
        $('#dcr_qd_mobile').val('');
        $('#dcr_qd_email').val('');
        $('#dcr_qd_speciality').val('');
        $('#dcr_qd_qualification').val('');
        $('#dcr_qd_address').val('');
        $('#dcr-quick-add-doctor-modal').modal('show');
    });

    $('#dcr-quick-add-doctor-save').on('click', function () {
        const fullname = ($('#dcr_qd_fullname').val() || '').trim();
        if (!fullname) {
            Swal.fire({ icon: 'warning', text: 'Full name is required.' });
            return;
        }
        const hqId = dcrGetHeadquarterIdForAjax();
        if (!hqId) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter first.' });
            return;
        }
        const data = {
            _token: '{{ csrf_token() }}',
            fullname: fullname,
            mobile: ($('#dcr_qd_mobile').val() || '').trim(),
            email: ($('#dcr_qd_email').val() || '').trim(),
            speciality: ($('#dcr_qd_speciality').val() || '').trim(),
            qualification: ($('#dcr_qd_qualification').val() || '').trim(),
            address: ($('#dcr_qd_address').val() || '').trim(),
            headquarter_id: hqId,
            station_type: selectedStationType,
            exstation_id: selectedStationType === 'exstation' ? selectedStationId : '',
            outstation_id: selectedStationType === 'outstation' ? selectedStationId : '',
        };
        const $btn = $(this);
        $btn.prop('disabled', true);
        $.easyAjax({
            url: "{{ route('dcr.create-doctor-inline') }}",
            type: 'POST',
            data: data,
            success: function (res) {
                $btn.prop('disabled', false);
                if (!res || res.status !== 'success' || !res.doctor) {
                    Swal.fire({ icon: 'error', text: (res && res.message) ? res.message : 'Could not create doctor.' });
                    return;
                }
                dcrPushDoctorToCatalog(res.doctor);
                refreshDoctorDropdowns();
                const vid = $('#dcr_quick_doctor_visit_id').val();
                if (vid) {
                    const $sel = $('.doctor-select[data-visit-id="' + vid + '"]');
                    if ($sel.length) {
                        $sel.selectpicker('val', String(res.doctor.id));
                        $sel.trigger('changed.bs.select');
                    }
                }
                $('#dcr-quick-add-doctor-modal').modal('hide');
                Swal.fire({ icon: 'success', text: res.message || 'Doctor added.', toast: true, position: 'top-end', timer: 2500, showConfirmButton: false });
            },
            error: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // Build header and visit payload for DCR auto-save (SRS 3.2.5)
    function getDcrHeaderForAutoSave() {
        const reportDate = $('#report_date').val();
        let headquarterId = $('#headquarter_id').val();
        if (!headquarterId && $('#headquarter_id').length && $('#headquarter_id').is('input')) {
            headquarterId = $('#headquarter_id').val();
        }
        if (!headquarterId) headquarterId = '{{ $userHeadquarter ?? "" }}';
        const workStatus = $('#work_status').selectpicker ? $('#work_status').selectpicker('val') : $('#work_status').val();
        const station = $('#station').val() || '';
        const workWith = $('#work_with').selectpicker ? $('#work_with').selectpicker('val') : $('#work_with').val();
        const submittedTo = $('#submitted_to').selectpicker ? $('#submitted_to').selectpicker('val') : $('#submitted_to').val();
        return { report_date: reportDate, headquarter_id: headquarterId, work_status: workStatus, station: station, work_with: Array.isArray(workWith) ? workWith : (workWith ? [workWith] : []), submitted_to: submittedTo };
    }
    function collectVisitDataFromCard($card, prefix) {
        const visitId = $card.data('visit-id');
        const data = {};
        const namePrefix = prefix + '[' + visitId + '][';
        $card.find('input, select, textarea').filter(function() {
            const n = $(this).attr('name');
            return n && n.indexOf(namePrefix) === 0;
        }).each(function() {
            const name = $(this).attr('name');
            const match = name.match(/\]\[([^\]]+)\]$/);
            if (match) data[match[1]] = $(this).val();
        });
        return data;
    }
    function autoSaveDcrVisit($card, visitType, visitData, btnEl) {
        const header = getDcrHeaderForAutoSave();
        if (!header.report_date || !header.headquarter_id || !header.work_status || !header.submitted_to) {
            Swal.fire({ icon: 'warning', text: 'Please fill Report Date, Headquarter, Work Type and Submit To first.', toast: true, position: 'top-end', timer: 3000 });
            return;
        }
        const payload = {
            _token: '{{ csrf_token() }}',
            report_date: header.report_date,
            headquarter_id: header.headquarter_id,
            work_status: header.work_status,
            station: header.station,
            submitted_to: header.submitted_to,
            visit_type: visitType
        };
        if (header.work_with && header.work_with.length) {
            payload.work_with = header.work_with;
        }
        const uid = $('#dcr_user_id').length ? $('#dcr_user_id').val() : '';
        if (uid) {
            payload.user_id = uid;
        }
        const serverVisitId = $card.attr('data-server-visit-id');
        if (serverVisitId) {
            payload.visit_id = parseInt(serverVisitId, 10);
        }
        payload[visitType] = visitData;
        const $btn = $(btnEl);
        const isLocationBtn = $btn.hasClass('get-location');
        const restoreHtml = $btn.data('dcr-restore-html') || $btn.html();
        if (!$btn.data('dcr-restore-html')) {
            $btn.data('dcr-restore-html', restoreHtml);
        }
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        $.ajax({
            url: "{{ route('dcr.store-visit') }}",
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function(res) {
            $card.attr('data-auto-saved', '1');
            if (res.dcr_id) {
                $card.attr('data-dcr-id', res.dcr_id);
            }
            if (res.visit_id) {
                $card.attr('data-server-visit-id', res.visit_id);
            }
            $btn.prop('disabled', false).html($btn.data('dcr-restore-html'));
            Swal.fire({ icon: 'success', text: res.message || 'Call saved.', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
            dcrEnableCloseDaySubmit();
            dcrUpdateFooterDraftSaved();
        }).fail(function(xhr) {
            $btn.prop('disabled', false).html($btn.data('dcr-restore-html'));
            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : (xhr.statusText || 'Request failed');
            Swal.fire({ icon: 'error', text: msg });
        });
    }

    $(document).on('click', '.dcr-save-visit', function () {
        const $card = $(this).closest('.doctor-visit-row, .chemist-visit-row, .stockist-visit-row');
        const visitType = $(this).data('visit-type');
        const prefix = visitType === 'doctor' ? 'doctors' : (visitType === 'chemist' ? 'chemists' : 'stockists');
        const visitData = collectVisitDataFromCard($card, prefix);
        autoSaveDcrVisit($card, visitType, visitData, this);
    });

    // GPS: Current location button (fills lat/lon in same card for 100m check); then auto-save call with geo (SRS 3.2.5)
    $(document).on('click', '.get-location', function() {
        const $card = $(this).closest('.card');
        const $lat = $card.find('.visit-lat');
        const $lon = $card.find('.visit-lon');
        const btnEl = this;
        if (!navigator.geolocation) {
            Swal.fire({ icon: 'warning', text: 'Geolocation is not supported by your browser.' });
            return;
        }
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Getting...');
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                $lat.val(pos.coords.latitude);
                $lon.val(pos.coords.longitude);
                const $locBtn = $card.find('.get-location');
                $locBtn.prop('disabled', false);
                if (!$locBtn.data('dcr-restore-html')) {
                    $locBtn.data('dcr-restore-html', '<i class="fa fa-map-marker-alt"></i> Current location');
                }
                $locBtn.html($locBtn.data('dcr-restore-html'));
                Swal.fire({ icon: 'success', text: 'Location captured.', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                if ($card.data('auto-saved') === '1') return;
                let visitType = null;
                let prefix = '';
                if ($card.hasClass('doctor-visit-row')) { visitType = 'doctor'; prefix = 'doctors'; }
                else if ($card.hasClass('chemist-visit-row')) { visitType = 'chemist'; prefix = 'chemists'; }
                else if ($card.hasClass('stockist-visit-row')) { visitType = 'stockist'; prefix = 'stockists'; }
                if (visitType && prefix) {
                    const visitData = collectVisitDataFromCard($card, prefix);
                    visitData.latitude = pos.coords.latitude;
                    visitData.longitude = pos.coords.longitude;
                    $locBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                    autoSaveDcrVisit($card, visitType, visitData, $locBtn[0]);
                }
            },
            function(err) {
                const $lb = $card.find('.get-location');
                $lb.prop('disabled', false);
                if ($lb.data('dcr-restore-html')) {
                    $lb.html($lb.data('dcr-restore-html'));
                } else {
                    $lb.html('<i class="fa fa-map-marker-alt"></i> Current location');
                }
                Swal.fire({ icon: 'error', text: err.message || 'Could not get location.' });
            }
        );
    });
    
    // Chemist visits management
    let chemistVisitCounter = 0;
    
    $('#add-chemist-btn').on('click', function() {
        if (!selectedStationType || !selectedStationId) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter and Station first.' });
            return;
        }
        addChemistVisit();
    });
    
    function addChemistVisit(draftRow) {
        chemistVisitCounter++;
        const visitId = chemistVisitCounter;
        
        // Build chemist options first using string concatenation
        const chemistsList = window.filteredChemists || [];
        let chemistOptions = '<option value="">-- Select Chemist --</option>';
        if (chemistsList.length === 0) {
            chemistOptions += '<option value="" disabled>No chemists for this station yet</option>';
        }
        chemistsList.forEach(function(raw) {
            if (!raw || !raw.id) return;
            const c = mergeCustomerFromAll(raw, window.dcrChemistCatalog || []);
            const areaName = c.area ? (c.area.name || c.area) : (c.area_name || '');
            const hqName = c.headquarter ? (c.headquarter.name || c.headquarter) : '';
            const exstationName = c.exstation ? (c.exstation.name || c.exstation) : '';
            const outstationName = c.outstation ? (c.outstation.name || c.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            
            chemistOptions += '<option value="' + c.id + '"' +
                ' data-station-name="' + (stationName || '') + '">' +
                (c.shopname || 'Unknown') +
            '</option>';
        });
        
        // Build RCPA product rows HTML
        const rcpaRowsHtml = [1, 2, 3, 4].map(function(i) {
            const productOptions = (allProducts || []).map(function(p) {
                return '<option value="' + (p.name || p.id || '') + '">' + (p.name || 'Unknown') + '</option>';
            }).join('');
            return '<tr>' +
                '<td>' +
                    '<select class="form-control" name="chemists[' + visitId + '][rcpa' + i + ']">' +
                        '<option value="">-- Select Product ' + i + ' --</option>' +
                        productOptions +
                    '</select>' +
                '</td>' +
                '<td><input type="number" class="form-control text-center" name="chemists[' + visitId + '][pob_amount' + i + ']" value="0" step="0.01" placeholder="0.00"></td>' +
                '<td><input type="text" class="form-control" name="chemists[' + visitId + '][remark' + i + ']" placeholder="Enter remark..."></td>' +
            '</tr>';
        }).join('');
        
        const chemistHtml = '<div class="card shadow-sm mb-4 chemist-visit-row" data-visit-id="' + visitId + '" data-server-visit-id="" data-dcr-id="" style="border-left: 4px solid #28a745;">' +
            '<div class="card-header bg-light d-flex justify-content-between align-items-center py-2">' +
                '<h6 class="mb-0">' +
                    '<span class="badge badge-success mr-2">#' + visitId + '</span>' +
                    '<i class="fa fa-flask text-success"></i> Chemist Visit' +
                '</h6>' +
                '<div class="d-flex align-items-center flex-wrap">' +
                    '<button type="button" class="btn btn-success btn-sm mr-2 mb-1 dcr-save-visit" data-visit-type="chemist" title="Save this visit to draft (GPS required when master has coordinates)">' +
                        '<i class="fa fa-save"></i> Save visit' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger btn-sm mb-1 remove-chemist-visit" data-visit-id="' + visitId + '">' +
                        '<i class="fa fa-trash"></i> Remove' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-8">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Chemist <span class="text-danger">*</span></label>' +
                            '<select class="form-control select-picker chemist-select" name="chemists[' + visitId + '][chemist_id]" data-visit-id="' + visitId + '" data-container="body" data-live-search="true" required>' +
                                chemistOptions +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Station</label>' +
                            '<input type="text" class="form-control bg-light chemist-station-name" name="chemists[' + visitId + '][station]" readonly>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="row">' +
                    '<div class="col-md-2">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">MSL Number</label>' +
                            '<input type="number" class="form-control" name="chemists[' + visitId + '][msl]" value="0" min="0" max="10" placeholder="0-10">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">GPS Location</label>' +
                            '<div class="input-group">' +
                                '<input type="hidden" name="chemists[' + visitId + '][latitude]" class="visit-lat" value="">' +
                                '<input type="hidden" name="chemists[' + visitId + '][longitude]" class="visit-lon" value="">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm get-location" title="Use current location for 100m check"><i class="fa fa-map-marker-alt"></i> Current location</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<hr class="my-3">' +
                '<h6 class="text-success mb-3"><i class="fa fa-prescription-bottle"></i> RCPA (Retail Chemist Prescription Audit)</h6>' +
                '<div class="table-responsive">' +
                    '<table class="table table-bordered table-hover">' +
                        '<thead class="thead-light">' +
                            '<tr>' +
                                '<th width="40%">Product</th>' +
                                '<th width="15%" class="text-center">Amount</th>' +
                                '<th width="45%">Remark</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>' +
                            rcpaRowsHtml +
                        '</tbody>' +
                    '</table>' +
                '</div>' +
                '<div class="row mt-3">' +
                    '<div class="col-md-12">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">General Remark</label>' +
                            '<textarea class="form-control" name="chemists[' + visitId + '][general_remark]" rows="2" placeholder="Overall remarks for this chemist visit..."></textarea>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('#chemist-visits-container').append(chemistHtml);
        $('.chemist-select[data-visit-id="' + visitId + '"]').selectpicker(dcrVisitSelectpickerOpts);
        
        // Auto-fill when chemist is selected
        $('.chemist-select[data-visit-id="' + visitId + '"]').on('changed.bs.select', function() {
            const selected = $(this).find(':selected');
            const visitRow = $(this).closest('.chemist-visit-row');
            visitRow.find('.chemist-station-name').val(selected.data('station-name') || '');
        });
        if (draftRow && draftRow.id && window.dcrDraftPayload) {
            const $card = $('#chemist-visits-container .chemist-visit-row[data-visit-id="' + visitId + '"]');
            $card.attr('data-server-visit-id', String(draftRow.id));
            $card.attr('data-dcr-id', String(window.dcrDraftPayload.dcr_id));
            $card.attr('data-auto-saved', '1');
            if (draftRow.chemist_id) {
                const $sel = $card.find('.chemist-select');
                if (!$sel.find('option[value="' + draftRow.chemist_id + '"]').length) {
                    const lab = (draftRow.chemist_label || ('Chemist #' + draftRow.chemist_id)).replace(/</g, '');
                    $sel.append('<option value="' + draftRow.chemist_id + '" data-station-name="">' + lab + '</option>');
                }
                $sel.selectpicker('refresh');
                $sel.selectpicker('val', String(draftRow.chemist_id));
                const selOpt = $sel.find('option:selected');
                $card.find('.chemist-station-name').val(draftRow.station || selOpt.data('station-name') || '');
            }
            $card.find('input[name="chemists[' + visitId + '][msl]"]').val(draftRow.msl != null ? draftRow.msl : 0);
            $card.find('.visit-lat').val(draftRow.latitude || '');
            $card.find('.visit-lon').val(draftRow.longitude || '');
            $card.find('textarea[name="chemists[' + visitId + '][general_remark]"]').val(draftRow.general_remark || '');
            for (let ci = 1; ci <= 4; ci++) {
                $card.find('select[name="chemists[' + visitId + '][rcpa' + ci + ']"]').val(draftRow['rcpa' + ci] || '');
                $card.find('input[name="chemists[' + visitId + '][pob_amount' + ci + ']"]').val(draftRow['pob_amount' + ci] != null ? draftRow['pob_amount' + ci] : 0);
                $card.find('input[name="chemists[' + visitId + '][remark' + ci + ']"]').val(draftRow['remark' + ci] || '');
            }
        }
    }
    
    $(document).on('click', '.remove-chemist-visit', function () {
        const $card = $(this).closest('.chemist-visit-row');
        dcrDeleteVisitRow($card, 'chemist', $(this).data('visit-id'));
    });
    
    // Stockist visits management
    let stockistVisitCounter = 0;
    
    $('#add-stockist-btn').on('click', function() {
        if (!selectedStationType || !selectedStationId) {
            Swal.fire({ icon: 'info', text: 'Please select Headquarter and Station first.' });
            return;
        }
        addStockistVisit();
    });
    
    function addStockistVisit(draftRow) {
        stockistVisitCounter++;
        const visitId = stockistVisitCounter;
        
        // Build stockist options first using string concatenation
        const stockistsList = window.filteredStockists || [];
        let stockistOptions = '<option value="">-- Select Stockist --</option>';
        if (stockistsList.length === 0) {
            stockistOptions += '<option value="" disabled>No stockists for this station yet</option>';
        }
        stockistsList.forEach(function(raw) {
            if (!raw || !raw.id) return;
            const s = mergeCustomerFromAll(raw, window.dcrStockistCatalog || []);
            const areaName = s.area ? (s.area.name || s.area) : (s.area_name || '');
            const hqName = s.headquarter ? (s.headquarter.name || s.headquarter) : '';
            const exstationName = s.exstation ? (s.exstation.name || s.exstation) : '';
            const outstationName = s.outstation ? (s.outstation.name || s.outstation) : '';
            const stationName = exstationName || outstationName || hqName;
            
            stockistOptions += '<option value="' + s.id + '"' +
                ' data-station-name="' + (stationName || '') + '">' +
                (s.shopname || 'Unknown') +
            '</option>';
        });
        
        const stockistHtml = '<div class="card shadow-sm mb-4 stockist-visit-row" data-visit-id="' + visitId + '" data-server-visit-id="" data-dcr-id="" style="border-left: 4px solid #ffc107;">' +
            '<div class="card-header bg-light d-flex justify-content-between align-items-center py-2">' +
                '<h6 class="mb-0">' +
                    '<span class="badge badge-warning mr-2">#' + visitId + '</span>' +
                    '<i class="fa fa-warehouse text-warning"></i> Stockist Visit' +
                '</h6>' +
                '<div class="d-flex align-items-center flex-wrap">' +
                    '<button type="button" class="btn btn-success btn-sm mr-2 mb-1 dcr-save-visit" data-visit-type="stockist" title="Save this visit to draft (GPS required when master has coordinates)">' +
                        '<i class="fa fa-save"></i> Save visit' +
                    '</button>' +
                    '<button type="button" class="btn btn-danger btn-sm mb-1 remove-stockist-visit" data-visit-id="' + visitId + '">' +
                        '<i class="fa fa-trash"></i> Remove' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="card-body">' +
                '<div class="row">' +
                    '<div class="col-md-8">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Stockist <span class="text-danger">*</span></label>' +
                            '<select class="form-control select-picker stockist-select" name="stockists[' + visitId + '][stockist_id]" data-visit-id="' + visitId + '" data-container="body" data-live-search="true" required>' +
                                stockistOptions +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Station</label>' +
                            '<input type="text" class="form-control bg-light stockist-station-name" name="stockists[' + visitId + '][station]" readonly>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="row">' +
                    '<div class="col-md-2">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">MSL Number</label>' +
                            '<input type="number" class="form-control" name="stockists[' + visitId + '][msl]" value="0" min="0" max="10" placeholder="0-10">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">GPS Location</label>' +
                            '<div class="input-group">' +
                                '<input type="hidden" name="stockists[' + visitId + '][latitude]" class="visit-lat" value="">' +
                                '<input type="hidden" name="stockists[' + visitId + '][longitude]" class="visit-lon" value="">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm get-location" title="Use current location for 100m check"><i class="fa fa-map-marker-alt"></i> Current location</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<hr class="my-3">' +
                '<h6 class="text-warning mb-3"><i class="fa fa-box"></i> Business Details</h6>' +
                '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">POB Description</label>' +
                            '<input type="text" class="form-control" name="stockists[' + visitId + '][pob]" placeholder="Enter POB details">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">POB Amount</label>' +
                            '<input type="number" class="form-control" name="stockists[' + visitId + '][pob_amount]" value="0" step="0.01" placeholder="0.00">' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="row">' +
                    '<div class="col-md-6">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Proprietor Name</label>' +
                            '<input type="text" class="form-control" name="stockists[' + visitId + '][proprietor]" placeholder="Enter proprietor name">' +
                        '</div>' +
                    '</div>' +
                    '<div class="col-md-6">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">Proprietor Mobile</label>' +
                            '<input type="text" class="form-control" name="stockists[' + visitId + '][proprietor_mobile]" placeholder="Enter mobile number">' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="row mt-3">' +
                    '<div class="col-md-12">' +
                        '<div class="form-group">' +
                            '<label class="font-weight-bold">General Remark</label>' +
                            '<textarea class="form-control" name="stockists[' + visitId + '][general_remark]" rows="2" placeholder="Overall remarks for this stockist visit..."></textarea>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('#stockist-visits-container').append(stockistHtml);
        $('.stockist-select[data-visit-id="' + visitId + '"]').selectpicker(dcrVisitSelectpickerOpts);
        
        // Auto-fill when stockist is selected
        $('.stockist-select[data-visit-id="' + visitId + '"]').on('changed.bs.select', function() {
            const selected = $(this).find(':selected');
            const visitRow = $(this).closest('.stockist-visit-row');
            visitRow.find('.stockist-station-name').val(selected.data('station-name') || '');
        });
        if (draftRow && draftRow.id && window.dcrDraftPayload) {
            const $card = $('#stockist-visits-container .stockist-visit-row[data-visit-id="' + visitId + '"]');
            $card.attr('data-server-visit-id', String(draftRow.id));
            $card.attr('data-dcr-id', String(window.dcrDraftPayload.dcr_id));
            $card.attr('data-auto-saved', '1');
            if (draftRow.stockist_id) {
                const $sel = $card.find('.stockist-select');
                if (!$sel.find('option[value="' + draftRow.stockist_id + '"]').length) {
                    const lab = (draftRow.stockist_label || ('Stockist #' + draftRow.stockist_id)).replace(/</g, '');
                    $sel.append('<option value="' + draftRow.stockist_id + '" data-station-name="">' + lab + '</option>');
                }
                $sel.selectpicker('refresh');
                $sel.selectpicker('val', String(draftRow.stockist_id));
                const selOpt = $sel.find('option:selected');
                $card.find('.stockist-station-name').val(draftRow.station || selOpt.data('station-name') || '');
            }
            $card.find('input[name="stockists[' + visitId + '][msl]"]').val(draftRow.msl != null ? draftRow.msl : 0);
            $card.find('.visit-lat').val(draftRow.latitude || '');
            $card.find('.visit-lon').val(draftRow.longitude || '');
            $card.find('input[name="stockists[' + visitId + '][pob]"]').val(draftRow.pob || '');
            $card.find('input[name="stockists[' + visitId + '][pob_amount]"]').val(draftRow.pob_amount != null ? draftRow.pob_amount : 0);
            $card.find('input[name="stockists[' + visitId + '][proprietor]"]').val(draftRow.proprietor || '');
            $card.find('input[name="stockists[' + visitId + '][proprietor_mobile]"]').val(draftRow.proprietor_mobile || '');
            $card.find('textarea[name="stockists[' + visitId + '][general_remark]"]').val(draftRow.general_remark || '');
        }
    }
    
    $(document).on('click', '.remove-stockist-visit', function () {
        const $card = $(this).closest('.stockist-visit-row');
        dcrDeleteVisitRow($card, 'stockist', $(this).data('visit-id'));
    });
    
    
    // Populate stations when HQ changes (for admin only)
    $('#headquarter_id').on('changed.bs.select', function() {
        if (!isAdmin) return; // Non-admin users already have all accessible stations loaded
        
        const hqId = $(this).val();
        if (hqId) {
            populateStationsByHQ(hqId);
        } else {
            // Clear stations if no HQ selected
            const $stationSelect = $('#station');
            $stationSelect.find('option:not(:first)').remove();
            $stationSelect.selectpicker('refresh');
        }
    });
    
    // When "Add DCR for" employee is changed, load that employee's context (report date, Submit To, HQ)
    $('#dcr_employee_id').on('changed.bs.select', function() {
        const empId = $(this).val();
        $('#dcr_user_id').val(empId || '');
        if (!empId) return; // Self: keep current form values
        $.easyAjax({
            url: "{{ route('dcr.get-context-for-employee') }}",
            type: "GET",
            data: { user_id: empId },
            success: function(response) {
                if (response.status !== 'success') return;
                $('#report_date').val(response.report_date);
                const headquarterSelect = $('#headquarter_id');
                if (headquarterSelect.length && headquarterSelect.is('select') && response.user_headquarter) {
                    headquarterSelect.val(response.user_headquarter);
                    headquarterSelect.selectpicker('refresh');
                    if (typeof populateStationsByHQ === 'function') {
                        populateStationsByHQ(response.user_headquarter);
                    }
                }
                const $submittedTo = $('#submitted_to');
                $submittedTo.find('option:not(:first)').remove();
                (response.managers || []).forEach(function(m) {
                    $submittedTo.append($('<option>', { value: m.id, text: m.name + (m.designation ? ' (' + m.designation + ')' : '') }));
                });
                if (response.reporting_manager_id) {
                    $submittedTo.val(response.reporting_manager_id);
                }
                $submittedTo.selectpicker('refresh');
                $('#report_date').trigger('change');
            }
        });
    });
    
    // Load tour plan when date changes (pass user_id when adding DCR for another employee)
    $('#report_date').on('change', function() {
        const date = $(this).val();
        if (!date) return;
        const data = {
            '_token': '{{ csrf_token() }}',
            'date': date
        };
        const empId = $('#dcr_employee_id').length ? $('#dcr_employee_id').val() : null;
        if (empId) data.user_id = empId;
        
        $.easyAjax({
            url: "{{ route('dcr.get-tour-by-date') }}",
            type: "POST",
            data: data,
            success: function(response) {
                if (window.dcrDraftLoaded && window.dcrDraftPayload) {
                    if (response.status == 'success' && response.tour) {
                        const tour = response.tour;
                        const workTypeDisplay = tour.work_status || 'Field Work';
                        const stationDisplay = tour.station || tour.headquarter;
                        $('#tour-alert-text').html(`Tour plan (reference): <strong>${workTypeDisplay}</strong> at <strong>${stationDisplay}</strong> — your saved draft is shown below.`);
                        $('#tour-alert').slideDown();
                    }
                    return;
                }
                if (response.status == 'success' && response.tour) {
                    const tour = response.tour;
                    const workTypeDisplay = tour.work_status || 'Field Work';
                    const stationDisplay = tour.station || tour.headquarter;
                    $('#tour-alert-text').html(`According to Tour Plan: <strong>${workTypeDisplay}</strong> at <strong>${stationDisplay}</strong> is Approved`);
                    $('#tour-alert').slideDown();

                    if (tour.headquarter_bundle) {
                        dcrMergeTourHeadquarterBundle(tour.headquarter_bundle);
                        if (!isAdmin && typeof populateAllAccessibleStations === 'function') {
                            populateAllAccessibleStations();
                        }
                    }
                    
                    // Pre-fill headquarter_id from tour plan
                    if (tour.headquarter_id) {
                        let headquarterSelect = $('#headquarter_id');
                        console.log('Setting headquarter from tour:', tour.headquarter_id);
                        console.log('Headquarter element exists:', headquarterSelect.length > 0);
                        
                        // If element doesn't exist, create a hidden input
                        if (headquarterSelect.length === 0) {
                            console.log('Creating hidden headquarter input');
                            $('<input>').attr({
                                type: 'hidden',
                                name: 'headquarter_id',
                                id: 'headquarter_id',
                                value: tour.headquarter_id
                            }).appendTo('#dcr-form');
                            headquarterSelect = $('#headquarter_id');
                        }
                        
                        if (headquarterSelect.is('select')) {
                            // It's a dropdown (admin/ABM/RBM), set the value
                            headquarterSelect.val(tour.headquarter_id);
                            headquarterSelect.selectpicker('refresh');
                            console.log('Headquarter set to:', headquarterSelect.selectpicker('val'));
                            // Populate stations based on the selected HQ
                            if (typeof populateStationsByHQ === 'function') {
                                populateStationsByHQ(tour.headquarter_id);
                            }
                        } else {
                            // It's a hidden input (non-admin), ensure it's set
                            headquarterSelect.val(tour.headquarter_id);
                            console.log('Hidden headquarter set to:', headquarterSelect.val());
                        }
                    } else {
                        console.warn('Tour plan does not have headquarter_id:', tour);
                    }
                    
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
    
    // Trigger on page load (skip tour auto-fill when editing an existing header-only DCR)
    if (window.dcrEditMode) {
        if (isAdmin && $('#headquarter_id').length && $('#headquarter_id').is('select')) {
            const hqId = $('#headquarter_id').val();
            if (hqId && typeof populateStationsByHQ === 'function') {
                populateStationsByHQ(hqId);
            }
        }
        const editStation = @json(!empty($editingDcr) ? ($editingDcr->station ?? '') : '');
        if (editStation) {
            $('#station').val(editStation).selectpicker('refresh').trigger('changed.bs.select');
        }
        $('#work_status').selectpicker('refresh');
        toggleSectionsByWorkStatus();
    } else if (window.dcrDraftLoaded && window.dcrDraftPayload) {
        if (isAdmin && $('#headquarter_id').length && $('#headquarter_id').is('select')) {
            const hqIdDraft = $('#headquarter_id').val();
            if (hqIdDraft && typeof populateStationsByHQ === 'function') {
                populateStationsByHQ(hqIdDraft);
            }
        }
        $('#work_with').selectpicker('refresh');
        $('#submitted_to').selectpicker('refresh');
        toggleSectionsByWorkStatus();
        const stDraft = (window.dcrDraftPayload.station || '').trim();
        if (stDraft) {
            $('#station').val(stDraft).selectpicker('refresh').trigger('changed.bs.select');
        } else {
            // Draft has no station string — still restore saved visit cards (do not rely on fetchFilteredLists)
            if (window.dcrPendingDraftHydration && typeof isFieldWorkSelected === 'function' && isFieldWorkSelected()) {
                setTimeout(function () {
                    hydrateDraftVisitsFromPayload();
                    window.dcrPendingDraftHydration = false;
                }, 0);
            }
        }
    } else if ($('#report_date').val()) {
        $('#report_date').trigger('change');
    }
    
    // Handle form submission with AJAX
    $('#dcr-form').on('submit', function(e) {
        e.preventDefault();
        
        // Store reference to native form element
        // In jQuery submit handler, 'this' IS the native HTMLFormElement
        const formElement = this;
        
        // FIRST: Remove required attribute from hidden sections to prevent HTML5 validation errors
        // This must be done before any validation checks
        // Get work_status value properly from Bootstrap Selectpicker
        const workStatusSelect = $('#work_status');
        const workStatusValue = workStatusSelect.selectpicker ? workStatusSelect.selectpicker('val') : workStatusSelect.val();
        const isFieldWork = fieldWorkTypes.includes(workStatusValue);
        
        if (isFieldWork) {
            // Remove required from non-field work section (it's hidden)
            $('#non-field-work-section').find('input[required], textarea[required]').prop('required', false);
            // Field work sections are visible, keep required on them
        } else {
            // Remove required from ALL field work fields (they're hidden)
            // Target all selects with name starting with doctors[], chemists[], stockists[]
            $('#field-work-sections').find('select[name^="doctors"], select[name^="chemists"], select[name^="stockists"]').prop('required', false);
            $('#field-work-sections').find('input[required], textarea[required]').prop('required', false);
            // Non-field work section is visible, keep required on it
        }
        
        // Now validate required fields
        // For Bootstrap Selectpicker, use selectpicker('val') instead of val()
        const reportDate = $('#report_date').val();
        
        // Check if headquarter_id is a select or hidden input
        const headquarterSelect = $('#headquarter_id');
        let headquarterId = null;
        
        if (headquarterSelect.length > 0) {
            if (headquarterSelect.is('select')) {
                // It's a dropdown (admin/ABM/RBM)
                headquarterId = headquarterSelect.selectpicker('val');
                // If selectpicker returns null/empty, try regular val()
                if (!headquarterId) {
                    headquarterId = headquarterSelect.val();
                }
            } else {
                // It's a hidden input (non-admin)
                headquarterId = headquarterSelect.val();
            }
        }
        
        // Fallback: Check if user has a default headquarter from backend
        if (!headquarterId) {
            const defaultHQ = '{{ $userHeadquarter ?? '' }}';
            if (defaultHQ) {
                headquarterId = defaultHQ;
                // Set it in the form if it's a select
                if (headquarterSelect.is('select')) {
                    headquarterSelect.val(defaultHQ);
                    headquarterSelect.selectpicker('refresh');
                }
            }
        }
        
        // Reuse workStatusValue (already declared above)
        
        // Get submitted_to value (Bootstrap Selectpicker)
        const submittedToSelect = $('#submitted_to');
        const submittedToValue = submittedToSelect.selectpicker ? submittedToSelect.selectpicker('val') : submittedToSelect.val();
        
        // Debug: Log values to console
        console.log('Validation Check:');
        console.log('Report Date:', reportDate);
        console.log('Headquarter Element:', headquarterSelect.length > 0 ? 'Found' : 'Not Found');
        console.log('Headquarter Element Type:', headquarterSelect.is('select') ? 'Select' : 'Hidden Input');
        console.log('Headquarter ID:', headquarterId);
        console.log('Headquarter Raw Value:', headquarterSelect.val());
        console.log('Work Status:', workStatusValue);
        console.log('Submitted To:', submittedToValue);
        
        if (!reportDate || !headquarterId || !workStatusValue || !submittedToValue) {
            let missingFields = [];
            if (!reportDate) missingFields.push('Report Date');
            if (!headquarterId) missingFields.push('Headquarter');
            if (!workStatusValue) missingFields.push('Work Status');
            if (!submittedToValue) missingFields.push('Submit To');
            
            Swal.fire({
                icon: 'error',
                text: 'Please fill all required fields: ' + missingFields.join(', '),
                toast: true,
                position: 'top-end',
                timer: 4000,
                showConfirmButton: false
            });
            return;
        }
        
        // Reuse workStatusValue and isFieldWork (already declared above)
        
        // Validate remark for non-field work
        if (!isFieldWork) {
            const remark = $('#non_field_remark').val();
            if (!remark || remark.trim() === '') {
                Swal.fire({
                    icon: 'error',
                    text: 'Remark is required for non-field work',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
                return;
            }
        }
        
        // Verify formElement is a native HTMLFormElement (should always be true in submit handler)
        console.log('formElement instanceof HTMLFormElement:', formElement instanceof HTMLFormElement);
        console.log('formElement type:', typeof formElement);
        console.log('formElement tagName:', formElement.tagName);
        
        if (!(formElement instanceof HTMLFormElement)) {
            console.error('Form element is not a valid HTMLFormElement');
            return;
        }
        
        // Create FormData from native form element (this is the correct way)
        const formData = new FormData(formElement);
        
        // Ensure headquarter_id is in FormData (even if element doesn't exist)
        if (headquarterId) {
            formData.set('headquarter_id', headquarterId);
        }
        
        // Ensure Bootstrap Selectpicker values are included in FormData
        // For multi-select work_with field
        const workWithValues = $('#work_with').selectpicker('val');
        if (workWithValues && workWithValues.length > 0) {
            // Remove existing work_with entries and add fresh ones
            formData.delete('work_with[]');
            workWithValues.forEach(function(value) {
                formData.append('work_with[]', value);
            });
        }
        
        // Ensure work_status is in FormData
        if (workStatusValue) {
            formData.set('work_status', workStatusValue);
        }
        
        // Ensure submitted_to is in FormData
        if (submittedToValue) {
            formData.set('submitted_to', submittedToValue);
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Closing day...');
        
        // Debug: Log form data (remove in production)
        console.log('Submitting DCR Report...');
        console.log('Report Date:', reportDate);
        console.log('Headquarter ID:', headquarterId);
        console.log('Work Status:', workStatusValue);
        console.log('Work With:', workWithValues);
        console.log('Submitted To:', submittedToValue);
        
        // Use regular AJAX instead of easyAjax file mode since we're creating FormData ourselves
        $.ajax({
            url: $(this).attr('action'),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function(xhr) {
                // Set CSRF token header
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
            },
            success: function(response) {
                console.log('DCR Report saved successfully:', response);
                Swal.fire({
                    icon: 'success',
                    text: response.message || 'DCR Report saved successfully',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // Redirect after a short delay
                setTimeout(function() {
                    if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else {
                        window.location.href = "{{ route('dcr-management.index') }}";
                    }
                }, 1000);
            },
            error: function(response) {
                console.error('Error saving DCR Report:', response);
                submitBtn.prop('disabled', false).html(originalText);
                
                let errorMessage = 'Error saving DCR report';
                if (response.responseJSON && response.responseJSON.message) {
                    errorMessage = response.responseJSON.message;
                } else if (response.responseJSON && response.responseJSON.errors) {
                    const errors = response.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('<br>');
                } else if (response.responseText) {
                    try {
                        const errorData = JSON.parse(response.responseText);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                        // Ignore parse errors
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    html: errorMessage,
                    toast: true,
                    position: 'top-end',
                    timer: 5000,
                    showConfirmButton: false
                });
            }
        });
    });
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

