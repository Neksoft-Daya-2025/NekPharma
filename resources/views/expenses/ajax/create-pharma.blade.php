@php
$addExpenseCategoryPermission = user()->permission('manage_expense_category');
@endphp

@push('styles')
<style>
    /* Prevent Bootstrap Select from changing selected option color */
    #submitted_to + .dropdown-menu .dropdown-item.active.selected,
    #submitted_to + .dropdown-menu .dropdown-item.selected {
        background-color: transparent !important;
        color: inherit !important;
    }
    
    /* Keep selected option white/unchanged on hover */
    #submitted_to + .dropdown-menu .dropdown-item.active.selected:hover,
    #submitted_to + .dropdown-menu .dropdown-item.selected:hover,
    #submitted_to + .dropdown-menu .dropdown-item.active.selected:focus,
    #submitted_to + .dropdown-menu .dropdown-item.selected:focus {
        background-color: transparent !important;
        color: inherit !important;
    }
    
    /* Also prevent color change for the text span */
    #submitted_to + .dropdown-menu .dropdown-item.active.selected:hover .text,
    #submitted_to + .dropdown-menu .dropdown-item.selected:hover .text,
    #submitted_to + .dropdown-menu .dropdown-item.active.selected:focus .text,
    #submitted_to + .dropdown-menu .dropdown-item.selected:focus .text {
        color: inherit !important;
    }
    
    /* Expense table: prevent overlapping and fix spacing */
    #pharma-expense-table {
        width: 100%;
        min-width: 1400px;
        table-layout: fixed;
    }
    
    #pharma-expense-table thead th {
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
        padding: 10px 10px;
        font-size: 12px;
        line-height: 1.3;
        vertical-align: middle;
        background-color: #f8f9fa;
    }
    
    #pharma-expense-table tbody td {
        padding: 8px;
        vertical-align: middle;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        min-width: 0;
        display: block;
    }
    
    .pharma-expense-table-section {
        margin-top: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .pharma-expense-table-wrapper {
        margin-top: 0.5rem;
    }
    
    /* Col 1-2: Date, Day */
    #pharma-expense-table th:nth-child(1),
    #pharma-expense-table td:nth-child(1) { min-width: 95px; width: 95px; }
    #pharma-expense-table th:nth-child(2),
    #pharma-expense-table td:nth-child(2) { min-width: 50px; width: 50px; text-align: center; }
    
    /* Col 3-4: Town Worked, Worked With */
    #pharma-expense-table th:nth-child(3),
    #pharma-expense-table td:nth-child(3) { min-width: 120px; width: 120px; }
    #pharma-expense-table th:nth-child(4),
    #pharma-expense-table td:nth-child(4) { min-width: 130px; width: 130px; }
    
    /* Col 5-6: No. of Doctors Met, No. of Retailers Met */
    #pharma-expense-table th:nth-child(5),
    #pharma-expense-table th:nth-child(6),
    #pharma-expense-table td:nth-child(5),
    #pharma-expense-table td:nth-child(6) {
        min-width: 70px;
        width: 70px;
        text-align: center;
    }
    
    #pharma-expense-table td:nth-child(5) input,
    #pharma-expense-table td:nth-child(6) input {
        width: 60px !important;
        max-width: 60px;
        text-align: center;
        padding: 6px 4px;
    }
    
    /* Col 7: Head Quarter Visited */
    #pharma-expense-table th:nth-child(7),
    #pharma-expense-table td:nth-child(7) {
        min-width: 200px;
        width: 200px;
    }
    
    #pharma-expense-table td:nth-child(7) .row { margin: 0; }
    #pharma-expense-table td:nth-child(7) .col-6 { padding: 0 5px; }
    
    /* Col 8: Mode of Transport */
    #pharma-expense-table th:nth-child(8),
    #pharma-expense-table td:nth-child(8) { min-width: 100px; width: 100px; }
    
    /* Col 9-10: Km., Fare Rs. */
    #pharma-expense-table th:nth-child(9),
    #pharma-expense-table th:nth-child(10),
    #pharma-expense-table td:nth-child(9),
    #pharma-expense-table td:nth-child(10) {
        min-width: 85px;
        width: 85px;
        text-align: center;
    }
    
    #pharma-expense-table td:nth-child(9) input,
    #pharma-expense-table td:nth-child(10) input {
        width: 75px !important;
        max-width: 75px;
        text-align: center;
        padding: 6px 4px;
    }
    
    /* Col 11: Daily Allowances (HQ, Ex, O/S) */
    #pharma-expense-table th:nth-child(11),
    #pharma-expense-table td:nth-child(11) {
        min-width: 150px;
        width: 150px;
        padding: 8px 4px;
    }
    
    #pharma-expense-table td:nth-child(11) .row { margin: 0; }
    #pharma-expense-table td:nth-child(11) .col-4 { padding: 0 2px; }
    #pharma-expense-table td:nth-child(11) input {
        padding: 6px 2px;
        font-size: 12px;
    }
    
    /* Col 12-13: Fixed Expenses, Other Expenses */
    #pharma-expense-table th:nth-child(12),
    #pharma-expense-table th:nth-child(13),
    #pharma-expense-table td:nth-child(12),
    #pharma-expense-table td:nth-child(13) {
        min-width: 100px;
        width: 100px;
        text-align: center;
    }
    
    #pharma-expense-table td:nth-child(12) input,
    #pharma-expense-table td:nth-child(13) input {
        width: 90px !important;
        max-width: 90px;
        text-align: center;
        padding: 6px 4px;
    }
    
    /* Col 14: Total Expense */
    #pharma-expense-table th:nth-child(14),
    #pharma-expense-table td:nth-child(14) {
        min-width: 100px;
        width: 100px;
        text-align: center;
        font-weight: bold;
    }
    
    #pharma-expense-table td:nth-child(14) .row-total-expense {
        font-size: 14px;
        color: #28a745;
    }
    
    /* Col 15: Remarks */
    #pharma-expense-table th:nth-child(15),
    #pharma-expense-table td:nth-child(15) {
        min-width: 140px;
        width: 140px;
    }
    
    #pharma-expense-table td:nth-child(15) .remarks-input {
        width: 100%;
        min-width: 120px;
    }
</style>
@endpush

<x-form id="save-pharma-expense-data-form">
            <div class="add-client bg-white rounded">
                <!-- Header Section -->
                <div class="p-20 border-bottom-grey">
                    <div class="text-center mb-3">
                        <h3 class="mb-0 font-weight-bold">RYVA VITABOTICS</h3>
                        <h4 class="mb-0">EXPENSES STATEMENT</h4>
                    </div>
                    
                    @if($isLocked ?? false)
                        <div class="alert alert-{{ $lockStatus == 'approved' ? 'success' : 'warning' }} expense-locked-alert mt-3 mb-3" role="alert">
                            <h5 class="alert-heading">
                                <i class="fa fa-lock"></i> Expense Statement Locked
                            </h5>
                            <p class="mb-0">
                                @if($lockStatus == 'approved')
                                    This expense statement for {{ \Carbon\Carbon::parse($currentMonth . '-01')->format('F Y') }} has been <strong>approved</strong> and cannot be edited. Please contact admin to delete the approved statement if you need to make changes.
                                @elseif($lockStatus == 'pending')
                                    This expense statement for {{ \Carbon\Carbon::parse($currentMonth . '-01')->format('F Y') }} has been <strong>submitted</strong> and is pending approval. It cannot be edited until approved or deleted by admin.
                                @endif
                            </p>
                            <hr>
                            <p class="mb-0">
                                <a href="{{ route('expenses.status') }}?month={{ $currentMonth }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-eye"></i> View Expense Status
                                </a>
                            </p>
                        </div>
                    @endif
                    @if(!($isLocked ?? false) && ($hasRejected ?? false))
                        <div class="alert alert-info expense-returned-alert mt-3 mb-3" role="alert">
                            <h5 class="alert-heading">
                                <i class="fa fa-undo"></i> Expense statement returned for correction
                            </h5>
                            <p class="mb-2">Your expense statement for {{ \Carbon\Carbon::parse($currentMonth . '-01')->format('F Y') }} was not approved. Please correct the details below and resubmit.</p>
                            @if(!empty($rejectedReasons))
                                <p class="mb-0"><strong>Reason(s) from manager/admin:</strong></p>
                                <ul class="mb-0 pl-3">
                                    @foreach($rejectedReasons as $reason)
                                        <li>{{ $reason }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                    
                    <div class="row mt-4">
                        @if (user()->permission('add_expenses') == 'all')
                            {{-- Admin can select employee and headquarter (like Tour Plan) --}}
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="pharma_user_id" :fieldLabel="__('Name of Employee')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="pharma_user_id" id="pharma_user_id"
                                        data-live-search="true" data-size="8" required>
                                        <option value="">-- Select Employee --</option>
                                        @foreach ($employees as $item)
                                            <x-user-option :user="$item" :employeeSelect="true" :selected="user()->id == $item->id" />
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                                <small class="form-text text-muted d-block">Select employee for expense</small>
                                <small class="form-text text-dark f-12" id="pharma-employee-id-line-admin" style="min-height: 1.2em;">
                                    <strong>{{ __('modules.employees.employeeId') }}:</strong> <span id="pharma-employee-id-display">—</span>
                                </small>
                            </div>
                            
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="pharma_headquarter_id" :fieldLabel="__('Head Quarter')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="pharma_headquarter_id" id="pharma_headquarter_id"
                                        data-live-search="true" data-size="8" required data-html="true">
                                        <option value="">-- Select HeadQuarter --</option>
                                        @foreach ($headquarters as $hq)
                                            <option value="{{ $hq->id }}" @selected($hq->id == $currentUserHeadquarter)>
                                                {{ $hq->name }}
                                                @if($hq->area) ({{ $hq->area->name }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                                <small class="form-text text-muted">Select HQ to load stations</small>
                            </div>
                        @else
                            {{-- Employee - show readonly fields with their details (like Tour Plan) --}}
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="pharma_user_id" :fieldLabel="__('Name of Employee')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <input type="hidden" name="pharma_user_id" value="{{ user()->id }}">
                                    <div class="form-control height-35 f-14 bg-light" style="display: flex; flex-direction: column; align-items: flex-start; justify-content: center;">
                                        <div style="display: flex; align-items: center;">
                                            <span class="badge badge-success mr-2">
                                                <i class="fa fa-lock"></i>
                                            </span>
                                            {{ user()->name }}
                                        </div>
                                        @php
                                            $selfEmpId = user()->employeeDetail?->employee_id ?? user()->employeeDetails?->employee_id;
                                        @endphp
                                        @if($selfEmpId)
                                            <small class="text-dark pl-0 mt-1"><strong>{{ __('modules.employees.employeeId') }}:</strong> {{ $selfEmpId }}</small>
                                        @endif
                                    </div>
                                </x-forms.input-group>
                                <small class="form-text text-muted">Your profile</small>
                            </div>
                            
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="pharma_headquarter_id" :fieldLabel="__('Head Quarter')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    @if(isset($headquarters) && $headquarters->isNotEmpty() && ($headquarters->count() > 1 || ($showHqDropdownForPharmaRoles ?? false)))
                                        {{-- ABM/RBM/ZM with multiple mapped HQs: show dropdown to select which HQ --}}
                                        <select class="form-control select-picker" name="pharma_headquarter_id" id="pharma_headquarter_id"
                                            data-live-search="true" data-size="8" required data-html="true">
                                            <option value="">-- Select HeadQuarter --</option>
                                            @foreach ($headquarters as $hq)
                                                <option value="{{ $hq->id }}" @selected($hq->id == $currentUserHeadquarter)>
                                                    {{ $hq->name }}
                                                    @if($hq->area) ({{ $hq->area->name }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Select HQ (your mapped headquarters)</small>
                                    @elseif($currentUserHeadquarter)
                                        <input type="hidden" name="pharma_headquarter_id" value="{{ $currentUserHeadquarter }}">
                                        <div class="form-control height-35 f-14 bg-light" style="display: flex; align-items: center;">
                                            <span class="badge badge-success mr-2">
                                                <i class="fa fa-lock"></i>
                                            </span>
                                            {{ $currentUserHeadquarterName ?? '--' }}
                                        </div>
                                        <small class="form-text text-muted">Your assigned headquarter</small>
                                    @else
                                        <div class="form-control height-35 f-14 bg-danger text-white">
                                            <i class="fa fa-exclamation-triangle"></i> Not Assigned
                                        </div>
                                        <small class="form-text text-danger">Contact admin to assign a headquarter</small>
                                    @endif
                                </x-forms.input-group>
                            </div>
                        @endif
                        
                        <div class="col-md-1">
                            <x-forms.label class="mt-3" fieldId="expense_month" :fieldLabel="__('Month')">
                            </x-forms.label>
                            <x-forms.input-group>
                                @php
                                    $currentMonth = now()->month;
                                    $currentYear = now()->year;
                                @endphp
                                <select class="form-control select-picker" name="expense_month" id="expense_month" required data-html="true">
                                    <x-forms.months :selectedMonth="$currentMonth" fieldRequired="true"/>
                                </select>
                            </x-forms.input-group>
                        </div>
                        
                        <div class="col-md-1">
                            <x-forms.label class="mt-3" fieldId="expense_year" :fieldLabel="__('Year')">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="expense_year" id="expense_year" required data-html="true">
                                    @php
                                        // Generate years from current year to 2 years back
                                        for ($i = 0; $i <= 2; $i++) {
                                            $year = $currentYear - $i;
                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                            echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                                        }
                                    @endphp
                                </select>
                            </x-forms.input-group>
                        </div>
                        
                        <div class="col-md-2">
                            <x-forms.datepicker fieldId="posted_on" fieldRequired="true"
                                :fieldLabel="__('Posted on')" fieldName="posted_on"
                                :fieldPlaceholder="__('placeholders.date')"
                                :fieldValue="\Carbon\Carbon::today()->format(company()->date_format)" />
                        </div>
                        
                        <div class="col-md-2">
                            <x-forms.number fieldId="no_of_vouchers" :fieldLabel="__('NO. OF VOUCHERS ATTACHED')"
                                fieldName="no_of_vouchers" fieldValue="0" fieldRequired="true" />
                        </div>
                        
                        <div class="col-md-3">
                            <x-forms.label class="mt-3" fieldId="submitted_to" :fieldLabel="__('Submit To (Manager)')">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="submitted_to" id="submitted_to"
                                    data-live-search="false" data-size="8" required data-html="true">
                                    @if(isset($reportingManagerId) && $reportingManagerId)
                                        @php
                                            $reportingManager = $managers->firstWhere('id', $reportingManagerId);
                                        @endphp
                                        @if($reportingManager)
                                            <option value="{{ $reportingManager->id }}" selected>
                                                ⭐ {{ $reportingManager->name }} (Your Reporting Manager)
                                                @if($reportingManager->employeeDetail && $reportingManager->employeeDetail->designation)
                                                    - {{ $reportingManager->employeeDetail->designation->name }}
                                                @endif
                                            </option>
                                        @else
                                            <option value="">-- Select Manager --</option>
                                            @foreach($managers as $manager)
                                                <option value="{{ $manager->id }}">
                                                    {{ $manager->name }}
                                                    @if($manager->employeeDetail && $manager->employeeDetail->designation)
                                                        ({{ $manager->employeeDetail->designation->name }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        @endif
                                    @else
                                        <option value="">-- Select Manager --</option>
                                        @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}">
                                                {{ $manager->name }}
                                                @if($manager->employeeDetail && $manager->employeeDetail->designation)
                                                    ({{ $manager->employeeDetail->designation->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </x-forms.input-group>
                            <small class="form-text text-muted">
                                @if(isset($reportingManagerId) && $reportingManagerId && $reportingManager)
                                    <span class="text-success"><i class="fa fa-check-circle"></i> Your reporting manager is pre-selected</span>
                                @else
                                    <span class="text-warning"><i class="fa fa-exclamation-triangle"></i> No reporting manager assigned in HR</span>
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Expense Table Section -->
                <div class="p-20 pharma-expense-table-section">
                    <div class="table-responsive pharma-expense-table-wrapper">
                        <table class="table table-bordered" id="pharma-expense-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Town Worked</th>
                                    <th>Worked With</th>
                                    <th>No. of Doctors Met</th>
                                    <th>No. of Retailers Met</th>
                                    <th>Name of the Head Quarter Visited</th>
                                    <th>Mode of Transport</th>
                                    <th>Km. (To &amp; From)</th>
                                    <th>Fare Rs.</th>
                                    <th>
                                        <div>Daily Allowances</div>
                                        <div class="small text-muted mt-1" style="font-size: 11px;">
                                            <div class="row" style="margin: 0;">
                                                <div class="col-4 text-center">HQ. Rs</div>
                                                <div class="col-4 text-center">Ex Rs.</div>
                                                <div class="col-4 text-center">O/S Rs</div>
                                            </div>
                                        </div>
                                    </th>
                                    <th>Fixed Expenses</th>
                                    <th>Other Expenses</th>
                                    <th>Total Expense</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="expense-rows">
                                <!-- Rows will be generated for entire month automatically -->
                            </tbody>
                            <tfoot id="expense-totals">
                                <tr class="bg-light font-weight-bold" style="border-top: 2px solid #333;">
                                    <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                                    <td class="text-right"><span id="total-doctors">0</span></td>
                                    <td class="text-right"><span id="total-retailers">0</span></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Km.</div>
                                        <div><span id="total-km" style="font-weight: bold;">0.00</span></div>
                                    </td>
                                    <td class="text-right">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Fare</div>
                                        <div><span id="total-fare" style="font-weight: bold;">0.00</span></div>
                                    </td>
                                    <td style="padding: 8px;">
                                        <div class="row" style="margin: 0;">
                                            <div class="col-4 text-right" style="padding: 0 8px; border-right: 1px solid #ccc;">
                                                <div style="font-size: 13px; color: #666; margin-bottom: 2px;">HQ. Rs</div>
                                                <div><span id="total-hq-rs" style="font-weight: bold;">0.00</span></div>
                                            </div>
                                            <div class="col-4 text-right" style="padding: 0 8px; border-right: 1px solid #ccc;">
                                                <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Ex Rs.</div>
                                                <div><span id="total-ex-rs" style="font-weight: bold;">0.00</span></div>
                                            </div>
                                            <div class="col-4 text-right" style="padding: 0 8px;">
                                                <div style="font-size: 13px; color: #666; margin-bottom: 2px;">O/S Rs</div>
                                                <div><span id="total-os-rs" style="font-weight: bold;">0.00</span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Fixed</div>
                                        <div><span id="total-fixed" style="font-weight: bold;">0.00</span></div>
                                    </td>
                                    <td class="text-right">
                                        <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Other</div>
                                        <div><span id="total-other" style="font-weight: bold;">0.00</span></div>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="p-20 border-top-grey">
                    <h5 class="mb-3 font-weight-bold">EXPENSE SUMMARY</h5>
                    <div class="row">
                        <!-- Statistics Card -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">Activity Summary</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Total Days:</span>
                                        <strong id="summary-total-days">0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Doctor Calls:</span>
                                        <strong id="summary-total-doctors">0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Chemist Calls:</span>
                                        <strong id="summary-total-retailers">0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Km:</span>
                                        <strong id="summary-total-km">0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expense Breakdown Card -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">Expense Breakdown</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Fare:</span>
                                        <strong id="summary-total-fare">₹0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Daily Allowances:</span>
                                        <strong id="summary-total-allowances">₹0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Fixed Expenses:</span>
                                        <strong id="summary-total-fixed">₹0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Other Expenses:</span>
                                        <strong id="summary-total-other">₹0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Total Card -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <h6 class="card-title mb-3" style="color: rgba(255,255,255,0.9);">Total Expense</h6>
                                    <div class="text-center">
                                        <h2 class="mb-0 font-weight-bold" id="summary-grand-total">₹0.00</h2>
                                        <small style="color: rgba(255,255,255,0.8);">Grand Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Allowances Breakdown -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">Daily Allowances Breakdown</h6>
                                    <div class="row">
                                        <div class="col-md-4 text-center border-right">
                                            <div class="mb-2">
                                                <small class="text-muted d-block">HQ. Rs</small>
                                                <strong class="h5" id="summary-hq-rs">₹0.00</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center border-right">
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Ex Rs.</small>
                                                <strong class="h5" id="summary-ex-rs">₹0.00</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="mb-2">
                                                <small class="text-muted d-block">O/S Rs</small>
                                                <strong class="h5" id="summary-os-rs">₹0.00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Upload Section -->
                <div class="p-20 border-top-grey">
                    <h5 class="mb-3 font-weight-bold">VOUCHER ATTACHMENTS</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <x-forms.label class="mt-3" fieldId="vouchers" :fieldLabel="__('Upload Vouchers')">
                            </x-forms.label>
                            <div class="form-group">
                                <input type="file" 
                                       class="form-control-file" 
                                       id="vouchers" 
                                       name="vouchers[]" 
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       multiple>
                                <small class="form-text text-muted">
                                    <i class="fa fa-info-circle"></i> You can upload multiple vouchers. Accepted formats: PDF, JPG, PNG. Maximum file size: 10MB per file.
                                </small>
                            </div>
                            <div id="voucher-preview" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <x-form-actions>
                    @if($isLocked ?? false)
                        <x-forms.button-primary id="save-pharma-expense-form" class="mr-3" icon="lock" disabled>
                            Form Locked
                        </x-forms.button-primary>
                        <x-forms.button-cancel :link="route('expenses.status')" class="border-0">View Status
                        </x-forms.button-cancel>
                    @else
                        <x-forms.button-primary id="save-pharma-expense-form" class="mr-3" icon="check">Submit
                        </x-forms.button-primary>
                        <x-forms.button-cancel :link="route('expenses.index')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    @endif
                </x-form-actions>
            </div>
        </x-form>

<script>
    $(document).ready(function() {
        // Data from controller (like Tour Plan)
        const headquarters = @json($headquarters);
        const workedWithDesignations = @json($workedWithDesignations);
        const isAdmin = {{ user()->permission('add_expenses') == 'all' ? 'true' : 'false' }};
        const userHeadquarter = {{ $userHeadquarter ?? 'null' }};
        
        // Headquarter options HTML (generated server-side)
        const headquarterOptions = `@foreach ($headquarters as $hq)
            <option value="{{ $hq->id }}">{{ $hq->name }}</option>
        @endforeach`;
        
        console.log('=== EXPENSE FORM INIT ===');
        console.log('isAdmin:', isAdmin);
        console.log('userHeadquarter:', userHeadquarter);
        console.log('headquarters count:', headquarters.length);
        console.log('workedWithDesignations:', workedWithDesignations);
        console.log('======================');
        
        // Generate headquarter options HTML (for Head Quarter Visited From/To)
        function getHeadquarterOptions() {
            let options = '<option value="">--</option>';
            
            if (!isAdmin) {
                // Non-admin (ABM/Employee): show all accessible headquarters and their stations
                // The headquarters array is already filtered by controller to show only accessible ones
                headquarters.forEach(hq => {
                    // Add headquarter itself
                    options += `<option value="${hq.name}">${hq.name} (Headquarter)</option>`;
                    
                    // Add exstations
                    if (hq.exstations && hq.exstations.length > 0) {
                        hq.exstations.forEach(station => {
                            options += `<option value="${station.name}">${station.name} (Ex Station)</option>`;
                        });
                    }
                    
                    // Add outstations
                    if (hq.outstations && hq.outstations.length > 0) {
                        hq.outstations.forEach(station => {
                            options += `<option value="${station.name}">${station.name} (Out Station)</option>`;
                        });
                    }
                });
            } else if (isAdmin) {
                // Admin: show all headquarters (when headquarter is selected, it will show stations)
                // For now, show all headquarters
                headquarters.forEach(hq => {
                    options += `<option value="${hq.id}">${hq.name}</option>`;
                });
            }
            return options;
        }
        
        // Town Worked dropdown: same options as HQ visited (station names). For admin, use selected HQ's stations.
        function getTownWorkedOptions() {
            if (isAdmin) {
                const hqId = $('#pharma_headquarter_id').val();
                if (hqId) {
                    const hq = headquarters.find(h => h.id == parseInt(hqId));
                    if (hq) {
                        let options = '<option value="">--</option>';
                        options += `<option value="${hq.name}">${hq.name} (Headquarter)</option>`;
                        if (hq.exstations && hq.exstations.length > 0) {
                            hq.exstations.forEach(station => { options += `<option value="${station.name}">${station.name} (Ex Station)</option>`; });
                        }
                        if (hq.outstations && hq.outstations.length > 0) {
                            hq.outstations.forEach(station => { options += `<option value="${station.name}">${station.name} (Out Station)</option>`; });
                        }
                        return options;
                    }
                }
                return '<option value="">--</option>';
            }
            return getHeadquarterOptions();
        }
        
        // Function to populate Head Quarter Visited dropdowns with selected headquarter's stations (for admin)
        function populateHeadquarterVisitedForAdmin(headquarterId) {
            if (isAdmin && headquarterId) {
                const hq = headquarters.find(h => h.id == parseInt(headquarterId));
                if (hq) {
                    let options = '<option value="">--</option>';
                    
                    // Add headquarter itself
                    options += `<option value="${hq.name}">${hq.name} (Headquarter)</option>`;
                    
                    // Add exstations
                    if (hq.exstations && hq.exstations.length > 0) {
                        hq.exstations.forEach(station => {
                            options += `<option value="${station.name}">${station.name} (Ex Station)</option>`;
                        });
                    }
                    
                    // Add outstations
                    if (hq.outstations && hq.outstations.length > 0) {
                        hq.outstations.forEach(station => {
                            options += `<option value="${station.name}">${station.name} (Out Station)</option>`;
                        });
                    }
                    
                    // Update all Head Quarter Visited dropdowns (From/To)
                    $('select[name*="[headquarter_from]"], select[name*="[headquarter_to]"]').html(options).selectpicker('refresh');
                    // Update all Town Worked dropdowns to match selected HQ
                    $('select.town-worked-select').html(options).selectpicker('refresh');
                }
            }
        }
        
        
        // Function to populate "Worked With" dropdown with designations
        function populateWorkedWithForDay(day) {
            const $workedWithSelect = $(`select[name="expenses[${day}][worked_with]"]`);
            
            // Destroy existing selectpicker if it exists
            if ($workedWithSelect.data('selectpicker')) {
                $workedWithSelect.selectpicker('destroy');
            }
            
            // Clear existing options
            $workedWithSelect.empty();
            
            // Add all designations
            workedWithDesignations.forEach(designation => {
                $workedWithSelect.append(`<option value="${designation}">${designation}</option>`);
            });
            
            // Initialize selectpicker with actionsBox (Select All / Deselect All buttons)
            $workedWithSelect.selectpicker({
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
        }
        
        // Function to populate all "Worked With" dropdowns
        function populateAllWorkedWithDropdowns() {
            $('select[name*="[worked_with]"]').each(function() {
                const nameMatch = $(this).attr('name').match(/expenses\[(\d+)\]\[worked_with\]/);
                if (nameMatch) {
                    const day = parseInt(nameMatch[1]);
                    populateWorkedWithForDay(day);
                }
            });
        }
        
        // Generate expense rows for entire month
        function generateMonthRows() {
            @php
                $currentMonth = now()->month;
                $currentYear = now()->year;
            @endphp
            // Get values from dropdowns
            const selectedMonth = $('#expense_month').val() || '{{ $currentMonth }}';
            const selectedYear = $('#expense_year').val() || '{{ $currentYear }}';
            
            if (!selectedMonth || !selectedYear) {
                return; // Don't generate if values are not available
            }
            
            const year = parseInt(selectedYear);
            const month = parseInt(selectedMonth);
            
            // Calculate days in month correctly
            // JavaScript Date months are 0-indexed (0-11), but our dropdown uses 1-12
            // Formula: new Date(year, month, 0) where month is 1-12 correctly gives the last day of the selected month
            // Examples:
            // - December (month=12): new Date(2025, 12, 0) = day 0 of month 12 (January 2026) = December 31, 2025 ✓
            // - January (month=1): new Date(2025, 1, 0) = day 0 of month 1 (February) = January 31, 2025 ✓
            const daysInMonth = new Date(year, month, 0).getDate();
            
            console.log('Generating expense rows - Year:', year, 'Month:', month, 'Days in Month:', daysInMonth);
            
            if (!daysInMonth || daysInMonth < 28 || daysInMonth > 31) {
                console.error('Invalid days in month calculated:', daysInMonth, 'for month', month, 'year', year);
                alert('Error: Could not calculate days for selected month. Please try again.');
                return;
            }
            
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            
            $('#expense-rows').empty();
            
            @php
                $dateFormat = company()->date_format;
                $dateFormatJs = str_replace(['d', 'm', 'Y'], ['DD', 'MM', 'YYYY'], $dateFormat);
            @endphp
            
            for (let day = 1; day <= daysInMonth; day++) {
                // JavaScript Date months are 0-indexed (0-11), so subtract 1 from month
                const date = new Date(year, month - 1, day);
                const dayName = dayNames[date.getDay()];
                
                // Format date according to company format directly from day, month, year
                // Ensure day and month are zero-padded
                const dayStr = String(day).padStart(2, '0');
                const monthStr = String(month).padStart(2, '0');
                const yearStr = String(year);
                
                let formattedDate = '';
                @if(company()->date_format == 'd-m-Y')
                    formattedDate = dayStr + '-' + monthStr + '-' + yearStr;
                @elseif(company()->date_format == 'm-d-Y')
                    formattedDate = monthStr + '-' + dayStr + '-' + yearStr;
                @else
                    formattedDate = yearStr + '-' + monthStr + '-' + dayStr;
                @endif
                
                // Check if expense exists for this day
                @php
                    $existingExpensesByDay = [];
                    if (isset($existingExpenses) && $existingExpenses->isNotEmpty()) {
                        foreach ($existingExpenses as $exp) {
                            $day = \Carbon\Carbon::parse($exp->purchase_date)->day;
                            $existingExpensesByDay[$day] = $exp;
                        }
                    }
                @endphp
                const existingExpenses = @json($existingExpensesByDay ?? []);
                const existingExpense = existingExpenses[day];
                const isRowLocked = existingExpense && (existingExpense.status === 'pending' || existingExpense.status === 'approved');
                
                // Build row with existing data or defaults
                const disabledAttr = isRowLocked ? 'disabled' : '';
                const readonlyClass = isRowLocked ? 'bg-light' : '';
                
                const expenseIdVal = existingExpense && existingExpense.id ? existingExpense.id : '';
                const row = '<tr class="expense-row ' + readonlyClass + '" data-row="' + day + '" data-expense-id="' + (existingExpense ? existingExpense.id : '') + '">' +
                    '<td>' + (expenseIdVal ? '<input type="hidden" name="expenses[' + day + '][expense_id]" value="' + expenseIdVal + '">' : '') + '<input type="text" class="form-control expense-date" name="expenses[' + day + '][date]" value="' + formattedDate + '" placeholder="{{ company()->date_format }}" readonly></td>' +
                    '<td><input type="text" class="form-control expense-day" name="expenses[' + day + '][day]" value="' + dayName + '" readonly></td>' +
                    '<td><select class="form-control select-picker town-worked-select" name="expenses[' + day + '][town_worked]" data-live-search="true" style="min-width: 120px;" ' + disabledAttr + ' data-html="true">' + getTownWorkedOptions() + '</select></td>' +
                    '<td><select class="form-control select-picker worked-with-select" name="expenses[' + day + '][worked_with]" multiple ' + disabledAttr + ' data-html="true"></select>' + 
                    (isRowLocked ? '<small class="text-muted d-block"><i class="fa fa-lock"></i> ' + (existingExpense.status === 'approved' ? 'Approved' : 'Pending') + '</small>' : '') + '</td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][no_of_doctors_met]" min="0" max="99" maxlength="2" style="width: 70px; text-align: center;" value="' + (existingExpense ? (existingExpense.no_of_doctors_met || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][no_of_retailers_met]" min="0" max="99" maxlength="2" style="width: 70px; text-align: center;" value="' + (existingExpense ? (existingExpense.no_of_retailers_met || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td style="min-width: 200px;"><div class="row" style="margin: 0;"><div class="col-6" style="padding: 0 5px;"><select class="form-control select-picker" name="expenses[' + day + '][headquarter_from]" data-live-search="true" style="width: 100%;" ' + disabledAttr + ' data-html="true">' + getHeadquarterOptions() + '</select></div>' +
                    '<div class="col-6" style="padding: 0 5px;"><select class="form-control select-picker" name="expenses[' + day + '][headquarter_to]" data-live-search="true" style="width: 100%;" ' + disabledAttr + ' data-html="true">' + getHeadquarterOptions() + '</select></div></div></td>' +
                    '<td><select class="form-control" name="expenses[' + day + '][mode_of_transport]" ' + disabledAttr + ' data-html="true"><option value="">--</option><option value="Bus">Bus</option><option value="Train">Train</option><option value="Flight">Flight</option><option value="Car">Car</option><option value="Bike">Bike</option><option value="Auto">Auto</option><option value="Taxi">Taxi</option><option value="Other">Other</option></select></td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][km]" step="0.01" min="0" max="999" maxlength="6" style="width: 85px; text-align: center;" value="' + (existingExpense ? (existingExpense.km || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][fare_rs]" step="0.01" min="0" max="999" maxlength="6" style="width: 85px; text-align: center;" value="' + (existingExpense ? (existingExpense.fare_rs || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td><div class="row"><div class="col-4"><input type="number" class="form-control" name="expenses[' + day + '][daily_allowance_hq_rs]" step="0.01" min="0" value="' + (existingExpense ? (existingExpense.daily_allowance_hq_rs || 0) : 0) + '" placeholder="HQ. Rs" ' + disabledAttr + '></div>' +
                    '<div class="col-4"><input type="number" class="form-control" name="expenses[' + day + '][daily_allowance_ex_rs]" step="0.01" min="0" value="' + (existingExpense ? (existingExpense.daily_allowance_ex_rs || 0) : 0) + '" placeholder="Ex Rs." ' + disabledAttr + '></div>' +
                    '<div class="col-4"><input type="number" class="form-control" name="expenses[' + day + '][daily_allowance_os_rs]" step="0.01" min="0" value="' + (existingExpense ? (existingExpense.daily_allowance_os_rs || 0) : 0) + '" placeholder="O/S Rs" ' + disabledAttr + '></div></div></td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][fixed_expenses]" step="0.01" min="0" max="999" maxlength="6" style="width: 85px; text-align: center;" value="' + (existingExpense ? (existingExpense.fixed_expenses || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td><input type="number" class="form-control" name="expenses[' + day + '][other_expenses]" step="0.01" min="0" max="999" maxlength="6" style="width: 85px; text-align: center;" value="' + (existingExpense ? (existingExpense.other_expenses || 0) : 0) + '" ' + disabledAttr + '></td>' +
                    '<td class="text-center"><strong class="row-total-expense" style="font-weight: bold; color: #28a745;">₹0.00</strong></td>' +
                    '<td><input type="text" class="form-control remarks-input" name="expenses[' + day + '][remarks]" value="' + (existingExpense ? (existingExpense.remarks || '') : '') + '" placeholder="Enter remarks..." ' + disabledAttr + '></td>' +
                    '</tr>';
                
                $('#expense-rows').append(row);
                
                // Calculate initial row total for this row
                const $row = $('#expense-rows tr.expense-row').last();
                calculateRowTotal($row);
            }
            
            console.log('Successfully generated', daysInMonth, 'expense rows for', month, '/', year);
            
            // Load existing expense data into rows (if available)
            @if(isset($existingExpensesData) && !empty($existingExpensesData))
                const existingExpensesData = @json($existingExpensesData);
                
                // Populate existing data into rows
                setTimeout(function() {
                    Object.keys(existingExpensesData).forEach(function(day) {
                        const dayNum = parseInt(day);
                        const expenseData = existingExpensesData[day];
                        const row = $(`.expense-row[data-row="${dayNum}"]`);
                        
                        if (row.length && expenseData) {
                            // Set worked with (multi-select)
                            if (expenseData.worked_with && Array.isArray(expenseData.worked_with) && expenseData.worked_with.length > 0) {
                                row.find('select[name*="[worked_with]"]').val(expenseData.worked_with);
                            }
                            
                            // Set headquarter from/to
                            if (expenseData.headquarter_from) {
                                row.find('select[name*="[headquarter_from]"]').val(expenseData.headquarter_from);
                            }
                            if (expenseData.headquarter_to) {
                                row.find('select[name*="[headquarter_to]"]').val(expenseData.headquarter_to);
                            }
                            
                            // Set mode of transport
                            if (expenseData.mode_of_transport) {
                                row.find('select[name*="[mode_of_transport]"]').val(expenseData.mode_of_transport);
                            }
                            
                            // Set remarks
                            if (expenseData.remarks) {
                                row.find('input[name*="[remarks]"]').val(expenseData.remarks);
                            }
                            
                            // Refresh selectpickers
                            row.find('.select-picker').selectpicker('refresh');
                        
                        // Calculate row total after loading data
                        calculateRowTotal(row);
                    }
                });
                
                // Calculate all row totals after loading existing data
                calculateAllRowTotals();
                }, 300);
            @endif
            
            // Initialize select pickers after all rows are added
            setTimeout(function() {
                // Populate "Worked With" dropdowns with designations (this will initialize selectpicker with actionsBox)
                populateAllWorkedWithDropdowns();
                
                // Refresh other select pickers (excluding worked-with-select as they're initialized above)
                $('.select-picker:not(.worked-with-select)').selectpicker('refresh');
                
                // Calculate totals after rows are generated
                calculateTotals();
                
                // Check for existing expenses after rows are generated and initialized
                // Skip if page was already locked on load (Blade template already shows alert)
                const wasLockedOnLoad = @json($isLocked ?? false);
                const selectedMonth = $('#expense_month').val();
                const selectedYear = $('#expense_year').val();
                if (selectedMonth && selectedYear && !wasLockedOnLoad) {
                    setTimeout(function() {
                        checkExistingExpenses(parseInt(selectedMonth), parseInt(selectedYear));
                    }, 200);
                } else if (wasLockedOnLoad) {
                    // Page was locked on load, just ensure form is disabled
                    $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', true);
                    $('#save-pharma-expense-form').prop('disabled', true).text('Form Locked').addClass('disabled');
                }
            }, 100);
        }
        
        // Function to check for existing expenses and lock ENTIRE form if ANY expense is submitted/approved
        function checkExistingExpenses(month, year) {
            const expenseMonth = String(year) + '-' + String(month).padStart(2, '0');
            
            // Check if page was already locked on load (from Blade template)
            const wasLockedOnLoad = @json($isLocked ?? false);
            
            $.ajax({
                url: "{{ route('expenses.check-existing') }}",
                type: "GET",
                data: {
                    month: month,
                    year: year,
                    expense_month: expenseMonth
                },
                success: function(response) {
                    // Remove info alerts
                    $('.expense-info-alert').remove();
                    
                    // Check if alert already exists (from Blade template on page load)
                    const existingAlert = $('.expense-locked-alert');
                    const alertExists = existingAlert.length > 0;
                    
                    if (response.has_expenses) {
                        const isLocked = response.is_locked || false;
                        const lockStatus = response.lock_status || null;
                        const existingExpenses = response.existing_expenses || {};
                        
                        if (isLocked) {
                            // Only add alert if it doesn't already exist (prevents duplicates)
                            // If page was locked on load, the Blade template already shows the alert
                            if (!alertExists) {
                                // Show lock message - ENTIRE form is locked
                                const alertHtml = '<div class="alert alert-' + (lockStatus === 'approved' ? 'success' : 'warning') + ' expense-locked-alert mt-3 mb-3" role="alert">' +
                                    '<h5 class="alert-heading"><i class="fa fa-lock"></i> Expense Statement Locked</h5>' +
                                    '<p class="mb-0">This expense statement for ' + getMonthName(month) + ' ' + year + ' has been <strong>' + (lockStatus === 'approved' ? 'approved' : 'submitted') + '</strong> and cannot be edited. Please contact admin to delete the statement if you need to make changes.</p>' +
                                    '<hr><p class="mb-0"><a href="{{ route("expenses.status") }}?month=' + expenseMonth + '" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> View Expense Status</a></p>' +
                                    '</div>';
                                $('.p-20.border-bottom-grey').after(alertHtml);
                            }
                            // If alert already exists (from Blade template), just ensure form is locked
                            
                            // Disable ALL form fields
                            $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', true);
                            $('#save-pharma-expense-form').prop('disabled', true).text('Form Locked').addClass('disabled');
                            
                            // Lock all rows and load existing data
                            loadExistingExpenseData(existingExpenses, true);
                        } else {
                            // Load existing data but don't lock - form remains editable
                            loadExistingExpenseData(existingExpenses, false);
                            // Ensure form is enabled
                            $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', false);
                            $('#save-pharma-expense-form').prop('disabled', false).text('Submit').removeClass('disabled');
                        }
                    } else {
                        // No expenses - ensure form is enabled
                        $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', false);
                        $('#save-pharma-expense-form').prop('disabled', false).text('Submit').removeClass('disabled');
                    }
                },
                error: function() {
                    console.error('Error checking existing expenses');
                }
            });
        }
        
        // Function to load existing expense data into rows
        function loadExistingExpenseData(existingExpenses, isLocked) {
            if (!existingExpenses || Object.keys(existingExpenses).length === 0) {
                return;
            }
            
            // Populate existing data into rows
            setTimeout(function() {
                Object.keys(existingExpenses).forEach(function(day) {
                    const dayNum = parseInt(day);
                    const expenseData = existingExpenses[day];
                    const row = $(`.expense-row[data-row="${dayNum}"]`);
                    
                    if (row.length && expenseData) {
                        // Set town worked
                        if (expenseData.town_worked) {
                            row.find('select[name*="[town_worked]"]').val(expenseData.town_worked);
                        }
                        
                        // Set worked with (multi-select)
                        if (expenseData.worked_with && Array.isArray(expenseData.worked_with) && expenseData.worked_with.length > 0) {
                            row.find('select[name*="[worked_with]"]').val(expenseData.worked_with);
                        }
                        
                        // Set headquarter from/to
                        if (expenseData.headquarter_from) {
                            row.find('select[name*="[headquarter_from]"]').val(expenseData.headquarter_from);
                        }
                        if (expenseData.headquarter_to) {
                            row.find('select[name*="[headquarter_to]"]').val(expenseData.headquarter_to);
                        }
                        
                        // Set mode of transport
                        if (expenseData.mode_of_transport) {
                            row.find('select[name*="[mode_of_transport]"]').val(expenseData.mode_of_transport);
                        }
                        
                        // Set remarks
                        if (expenseData.remarks) {
                            row.find('input[name*="[remarks]"]').val(expenseData.remarks);
                        }
                        
                        // If locked, disable row and add lock indicator
                        if (isLocked) {
                            row.addClass('bg-light');
                            row.find('input, select').prop('disabled', true);
                            const status = expenseData.status === 'approved' ? 'Approved' : 'Pending';
                            if (!row.find('small.text-muted').length) {
                                row.find('select[name*="[worked_with]"]').after('<small class="text-muted d-block"><i class="fa fa-lock"></i> ' + status + '</small>');
                            }
                        }
                        
                        // Refresh selectpickers
                        row.find('.select-picker').selectpicker('refresh');
                        
                        // Calculate row total after loading data
                        calculateRowTotal(row);
                    }
                });
                
                // Calculate all row totals after loading existing data
                setTimeout(function() {
                    calculateAllRowTotals();
                }, 500);
                
                // Recalculate totals
                calculateTotals();
            }, 300);
        }
        
        // Helper function to get month name
        function getMonthName(month) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
            return months[month - 1];
        }
        
        // Function to calculate and update totals
        function calculateTotals() {
            let totalDoctors = 0;
            let totalRetailers = 0;
            let totalKm = 0;
            let totalFare = 0;
            let totalHqRs = 0;
            let totalExRs = 0;
            let totalOsRs = 0;
            let totalFixed = 0;
            let totalOther = 0;
            
            // Calculate totals from all rows
            $('.expense-row').each(function() {
                const doctors = parseInt($(this).find('input[name*="[no_of_doctors_met]"]').val()) || 0;
                const retailers = parseInt($(this).find('input[name*="[no_of_retailers_met]"]').val()) || 0;
                const km = parseFloat($(this).find('input[name*="[km]"]').val()) || 0;
                const fare = parseFloat($(this).find('input[name*="[fare_rs]"]').val()) || 0;
                const hqRs = parseFloat($(this).find('input[name*="[daily_allowance_hq_rs]"]').val()) || 0;
                const exRs = parseFloat($(this).find('input[name*="[daily_allowance_ex_rs]"]').val()) || 0;
                const osRs = parseFloat($(this).find('input[name*="[daily_allowance_os_rs]"]').val()) || 0;
                const fixed = parseFloat($(this).find('input[name*="[fixed_expenses]"]').val()) || 0;
                const other = parseFloat($(this).find('input[name*="[other_expenses]"]').val()) || 0;
                
                totalDoctors += doctors;
                totalRetailers += retailers;
                totalKm += km;
                totalFare += fare;
                totalHqRs += hqRs;
                totalExRs += exRs;
                totalOsRs += osRs;
                totalFixed += fixed;
                totalOther += other;
            });
            
            // Update total displays
            $('#total-doctors').text(totalDoctors);
            $('#total-retailers').text(totalRetailers);
            $('#total-km').text(totalKm.toFixed(2));
            $('#total-fare').text(totalFare.toFixed(2));
            $('#total-hq-rs').text(totalHqRs.toFixed(2));
            $('#total-ex-rs').text(totalExRs.toFixed(2));
            $('#total-os-rs').text(totalOsRs.toFixed(2));
            $('#total-fixed').text(totalFixed.toFixed(2));
            $('#total-other').text(totalOther.toFixed(2));
            
            // Calculate grand total (include fixed expenses)
            const grandTotal = totalFare + totalHqRs + totalExRs + totalOsRs + totalFixed + totalOther;
            $('#grand-total').text(grandTotal.toFixed(2));
            
            // Update summary section
            const totalDays = $('.expense-row').filter(function() {
                const hasData = $(this).find('select[name*="[town_worked]"]').val() ||
                               $(this).find('select[name*="[worked_with]"]').val() ||
                               $(this).find('input[name*="[no_of_doctors_met]"]').val() > 0 ||
                               $(this).find('input[name*="[no_of_retailers_met]"]').val() > 0 ||
                               $(this).find('select[name*="[headquarter_from]"]').val() ||
                               $(this).find('select[name*="[headquarter_to]"]').val() ||
                               $(this).find('select[name*="[mode_of_transport]"]').val() ||
                               $(this).find('input[name*="[km]"]').val() > 0 ||
                               $(this).find('input[name*="[fare_rs]"]').val() > 0 ||
                               $(this).find('input[name*="[daily_allowance_hq_rs]"]').val() > 0 ||
                               $(this).find('input[name*="[daily_allowance_ex_rs]"]').val() > 0 ||
                               $(this).find('input[name*="[daily_allowance_os_rs]"]').val() > 0 ||
                               $(this).find('input[name*="[fixed_expenses]"]').val() > 0 ||
                               $(this).find('input[name*="[other_expenses]"]').val() > 0;
                return hasData;
            }).length;
            
            const totalAllowances = totalHqRs + totalExRs + totalOsRs;
            
            $('#summary-total-days').text(totalDays);
            $('#summary-total-doctors').text(totalDoctors);
            $('#summary-total-retailers').text(totalRetailers);
            $('#summary-total-km').text(totalKm.toFixed(2));
            $('#summary-total-fare').text('₹' + totalFare.toFixed(2));
            $('#summary-total-allowances').text('₹' + totalAllowances.toFixed(2));
            $('#summary-total-fixed').text('₹' + totalFixed.toFixed(2));
            $('#summary-total-other').text('₹' + totalOther.toFixed(2));
            $('#summary-grand-total').text('₹' + grandTotal.toFixed(2));
            $('#summary-hq-rs').text('₹' + totalHqRs.toFixed(2));
            $('#summary-ex-rs').text('₹' + totalExRs.toFixed(2));
            $('#summary-os-rs').text('₹' + totalOsRs.toFixed(2));
        }
        
        // Function to calculate and update row total expense
        function calculateRowTotal(row) {
            const fare = parseFloat(row.find('input[name*="[fare_rs]"]').val()) || 0;
            const hqRs = parseFloat(row.find('input[name*="[daily_allowance_hq_rs]"]').val()) || 0;
            const exRs = parseFloat(row.find('input[name*="[daily_allowance_ex_rs]"]').val()) || 0;
            const osRs = parseFloat(row.find('input[name*="[daily_allowance_os_rs]"]').val()) || 0;
            const fixed = parseFloat(row.find('input[name*="[fixed_expenses]"]').val()) || 0;
            const other = parseFloat(row.find('input[name*="[other_expenses]"]').val()) || 0;
            
            const rowTotal = fare + hqRs + exRs + osRs + fixed + other;
            row.find('.row-total-expense').text('₹' + rowTotal.toFixed(2));
        }
        
        // Calculate row totals for all rows
        function calculateAllRowTotals() {
            $('.expense-row').each(function() {
                calculateRowTotal($(this));
            });
        }
        
        // Update totals when any numeric input changes
        $(document).on('input change', 'input[name*="[no_of_doctors_met]"], input[name*="[no_of_retailers_met]"], input[name*="[km]"], input[name*="[fare_rs]"], input[name*="[daily_allowance_hq_rs]"], input[name*="[daily_allowance_ex_rs]"], input[name*="[daily_allowance_os_rs]"], input[name*="[fixed_expenses]"], input[name*="[other_expenses]"]', function() {
            calculateTotals();
            // Also update the row total for the current row
            const row = $(this).closest('.expense-row');
            if (row.length) {
                calculateRowTotal(row);
            }
        });
        
        // Function to load locations for a headquarter (follow Tour Plan pattern)
        // Note: Town Worked field has been removed, this function is kept for backward compatibility
        function loadHeadquarterLocations(headquarterId) {
            // This function is no longer needed but kept for backward compatibility
        }
        
        // Initialize select pickers for month and year dropdowns first
        $('#expense_month').selectpicker();
        $('#expense_year').selectpicker();
        
        // Initialize submitted_to selectpicker and ensure reporting manager is selected
        $('#submitted_to').selectpicker();
        
        // Ensure reporting manager is selected if available
        @if(isset($reportingManagerId) && $reportingManagerId)
            $('#submitted_to').selectpicker('val', '{{ $reportingManagerId }}');
        @endif

        // Show Employee ID next to "Name of Employee" (admin: updates when selection changes)
        function updatePharmaEmployeeIdFromSelect() {
            var $sel = $('#pharma_user_id');
            var $disp = $('#pharma-employee-id-display');
            if (!$sel.length || !$disp.length) {
                return;
            }
            var code = $sel.find('option:selected').attr('data-employee-id');
            $disp.text((code && String(code).length) ? code : '—');
        }
        $(document).on('changed.bs.select', '#pharma_user_id', updatePharmaEmployeeIdFromSelect);
        $(document).on('change', '#pharma_user_id', updatePharmaEmployeeIdFromSelect);
        setTimeout(updatePharmaEmployeeIdFromSelect, 100);
        
        // Load locations when headquarter changes (like Tour Plan) - Admin only
        $('#pharma_headquarter_id').on('changed.bs.select', function() {
            const headquarterId = $(this).val();
            console.log('HQ selector changed, ID:', headquarterId);
            if (headquarterId && isAdmin) {
                const hq = headquarters.find(h => h.id == parseInt(headquarterId));
                if (hq) {
                    console.log('Found HQ object:', hq);
                    loadHeadquarterLocations(headquarterId);
                    populateHeadquarterVisitedForAdmin(headquarterId);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'HeadQuarter Selected',
                        html: `<small>Stations loaded for <strong>${hq.name}</strong>.<br>You can now select stations and designations for each day.</small>`,
                        timer: 2500,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                }
            } else if (!headquarterId) {
                // Clear Head Quarter Visited dropdowns
                $('select[name*="[headquarter_from]"], select[name*="[headquarter_to]"]').html('<option value="">--</option>').selectpicker('refresh');
            }
        });
        
        $('#pharma_headquarter_id').on('change', function() {
            const headquarterId = $(this).val();
            if (headquarterId && isAdmin) {
                loadHeadquarterLocations(headquarterId);
                populateHeadquarterVisitedForAdmin(headquarterId);
            }
        });
        
        // Generate rows for initial month/year selection (after select pickers are initialized)
        setTimeout(function() {
            generateMonthRows();
            
            // Load initial locations if headquarter is already selected (like Tour Plan)
            const initialHeadquarterId = isAdmin ? $('#pharma_headquarter_id').val() : userHeadquarter;
            if (initialHeadquarterId) {
                setTimeout(function() {
                    // Head Quarter Visited is already populated via getHeadquarterOptions() in row generation
                    if (!isAdmin && userHeadquarter) {
                        
                        const empHq = headquarters.find(h => h.id == userHeadquarter);
                        if (empHq) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Employee Expense Statement',
                                html: `<strong>Your HeadQuarter:</strong> ${empHq.name}<br><br>
                                       <small>Stations and headquarters loaded. Select stations and designations for each day.</small>`,
                                timer: 3500,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false
                            });
                        }
                    } else if (isAdmin) {
                        // Admin: load locations when HQ is selected
                        loadHeadquarterLocations(initialHeadquarterId);
                        populateHeadquarterVisitedForAdmin(initialHeadquarterId);
                    }
                }, 200);
            } else if (!isAdmin && !userHeadquarter) {
                // Show error if employee has no headquarter (like Tour Plan)
                Swal.fire({
                    icon: 'error',
                    title: 'No HeadQuarter Assigned',
                    text: 'Please contact your administrator to assign a headquarter to your profile.',
                    confirmButtonText: 'OK'
                });
            } else if (isAdmin) {
                // Show info for admin to select HQ (like Tour Plan)
                Swal.fire({
                    icon: 'info',
                    title: 'Select HeadQuarter',
                    text: 'Please select a headquarter to populate stations',
                    timer: 3000,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            }
        }, 300);
        
        // Regenerate rows when month or year changes (like Tour Plan)
        $('#expense_month, #expense_year').on('changed.bs.select', function() {
            generateMonthRows();
            // Reload locations after rows are regenerated
            setTimeout(function() {
                // Head Quarter Visited is already populated via getHeadquarterOptions() in row generation
                if (isAdmin) {
                    // Admin: Load locations for selected headquarter
                    const headquarterId = $('#pharma_headquarter_id').val();
                    if (headquarterId) {
                        loadHeadquarterLocations(headquarterId);
                        populateHeadquarterVisitedForAdmin(headquarterId);
                    }
                }
            }, 200);
        });
        
        // Also handle regular change event as fallback
        $('#expense_month, #expense_year').on('change', function() {
            generateMonthRows();
            // Reload locations after rows are regenerated
            setTimeout(function() {
                // Head Quarter Visited is already populated via getHeadquarterOptions() in row generation
                if (isAdmin) {
                    // Admin: Load locations for selected headquarter
                    const headquarterId = $('#pharma_headquarter_id').val();
                    if (headquarterId) {
                        loadHeadquarterLocations(headquarterId);
                        populateHeadquarterVisitedForAdmin(headquarterId);
                    }
                }
            }, 200);
        });
        
        // Disable form if locked
        @if($isLocked ?? false)
            $(document).ready(function() {
                $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', true);
                $('#save-pharma-expense-form').prop('disabled', true).text('Form Locked').addClass('disabled');
            });
        @endif
        
        // Disable form if locked on page load
        @if($isLocked ?? false)
            $(document).ready(function() {
                $('#save-pharma-expense-data-form').find('input, select, textarea, button').not('[type="hidden"]').prop('disabled', true);
                $('#save-pharma-expense-form').prop('disabled', true).text('Form Locked').addClass('disabled');
            });
        @endif
        
        // Save form
        $('#save-pharma-expense-form').click(function() {
            // Prevent submission if form is locked (server-side check)
            @if($isLocked ?? false)
                Swal.fire({
                    icon: 'warning',
                    title: 'Form Locked',
                    text: 'This expense statement has been {{ $lockStatus == "approved" ? "approved" : "submitted" }} and cannot be edited. Please contact admin to delete the statement if you need to make changes.',
                    confirmButtonText: 'OK'
                });
                return false;
            @endif
            
            // Check if form is disabled (locked via AJAX)
            if ($('#save-pharma-expense-form').prop('disabled')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Form Locked',
                    text: 'This expense statement has been submitted or approved and cannot be edited. Please contact admin to delete the statement if you need to make changes.',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            
            // Filter out empty rows (rows with no data entered)
            const expenseRows = [];
            $('#expense-rows tr.expense-row').each(function() {
                const row = $(this);
                
                const hasData = row.find('select[name*="[worked_with]"]').val() ||
                               row.find('input[name*="[no_of_doctors_met]"]').val() > 0 ||
                               row.find('input[name*="[no_of_retailers_met]"]').val() > 0 ||
                               row.find('select[name*="[headquarter_from]"]').val() ||
                               row.find('select[name*="[headquarter_to]"]').val() ||
                               row.find('select[name*="[mode_of_transport]"]').val() ||
                               row.find('input[name*="[km]"]').val() > 0 ||
                               row.find('input[name*="[fare_rs]"]').val() > 0 ||
                               row.find('input[name*="[daily_allowance_hq_rs]"]').val() > 0 ||
                               row.find('input[name*="[daily_allowance_ex_rs]"]').val() > 0 ||
                               row.find('input[name*="[daily_allowance_os_rs]"]').val() > 0 ||
                               row.find('input[name*="[other_expenses]"]').val() > 0;
                
                if (hasData) {
                    expenseRows.push(row);
                }
            });
            
            if (expenseRows.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter expense data for at least one day.'
                });
                return false;
            }
            
            const url = "{{ route('expenses.store-pharma') }}";
            var formData = new FormData($('#save-pharma-expense-data-form')[0]);
            
            // Add voucher files to form data
            var voucherFiles = $('#vouchers')[0].files;
            for (var i = 0; i < voucherFiles.length; i++) {
                formData.append('vouchers[]', voucherFiles[i]);
            }
            
            $.easyAjax({
                url: url,
                container: '#save-pharma-expense-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-pharma-expense-form",
                data: formData,
                file: true,
                processData: false,
                contentType: false,
                success: function(response) {
                    window.location.href = response.redirectUrl || "{{ route('expenses.index') }}";
                }
            });
        });
        
        // Handle voucher file preview
        $('#vouchers').on('change', function() {
            var files = this.files;
            var preview = $('#voucher-preview');
            preview.empty();
            
            if (files.length > 0) {
                var fileList = $('<div class="row"></div>');
                
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB
                    var fileType = file.type;
                    var isImage = fileType.startsWith('image/');
                    var isPDF = fileType === 'application/pdf';
                    
                    var fileCard = $('<div class="col-md-3 mb-3"></div>');
                    var card = $('<div class="card border"></div>');
                    var cardBody = $('<div class="card-body p-2"></div>');
                    
                    if (isImage) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var img = $('<img class="card-img-top" style="height: 100px; object-fit: cover;">');
                            img.attr('src', e.target.result);
                            cardBody.prepend(img);
                        };
                        reader.readAsDataURL(file);
                    } else if (isPDF) {
                        var pdfIcon = $('<div class="text-center p-3"><i class="fa fa-file-pdf fa-3x text-danger"></i></div>');
                        cardBody.prepend(pdfIcon);
                    }
                    
                    var fileName = $('<p class="card-text mb-1 small text-truncate" title="' + file.name + '"><strong>' + file.name + '</strong></p>');
                    var fileSizeText = $('<p class="card-text mb-0 small text-muted">Size: ' + fileSize + ' MB</p>');
                    
                    cardBody.append(fileName);
                    cardBody.append(fileSizeText);
                    card.append(cardBody);
                    fileCard.append(card);
                    fileList.append(fileCard);
                }
                
                preview.append('<h6 class="mb-2">Selected Vouchers (' + files.length + '):</h6>');
                preview.append(fileList);
            }
        });
    });
</script>

