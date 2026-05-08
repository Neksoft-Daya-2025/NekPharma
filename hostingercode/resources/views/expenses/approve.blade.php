@extends('layouts.app')

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
    
    .month-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="month-header w-100 text-center">
            <h3 class="mb-0"><i class="fa fa-check-circle"></i> Approve Expense Statement</h3>
            <p class="mb-0 mt-2">Review, edit, and approve submitted expense statements</p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="month-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                    <i class="fa fa-calendar"></i> Select Month
                </label>
                <input type="month" class="form-control height-35 f-14" name="month" id="month-filter" 
                       value="{{ $currentMonth }}" onchange="window.location.href='{{ route('expenses.index') }}?month=' + this.value + '{{ $selectedEmployeeId ? '&employee_id=' . $selectedEmployeeId : '' }}'">
            </div>
            
            @if(user()->permission('view_expenses') == 'all')
                <div class="col-md-3">
                    <label for="employee-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        <i class="fa fa-user"></i> Select Employee
                    </label>
                    <select class="form-control height-35 f-14 select-picker" name="employee_id" id="employee-filter" 
                            data-live-search="true" data-html="true" onchange="window.location.href='{{ route('expenses.index') }}?month={{ $currentMonth }}&employee_id=' + this.value">
                        <option value="all" {{ (!$selectedEmployeeId || $selectedEmployeeId == 'all') ? 'selected' : '' }}>-- All Employees --</option>
                        @foreach($employees as $emp)
                            <x-user-option :user="$emp" :employeeSelect="true" :selected="$selectedEmployeeId == $emp->id" />
                        @endforeach
                    </select>
                </div>
            @endif
            
            <div class="col-md-3 d-flex align-items-end pb-3">
                <x-forms.link-primary :link="route('expenses.create')" class="mr-2" icon="plus">
                    Create Expense
                </x-forms.link-primary>
            </div>
        </div>
        
        @if($groupedExpenses->isEmpty())
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong>No pending expenses found</strong> for {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}
                @if(user()->permission('view_expenses') == 'all' && $selectedEmployeeId != 'all')
                    for the selected employee
                @endif
            </div>
        @endif

        @foreach($groupedExpenses as $groupKey => $groupExpenses)
            @php
                $firstExpense = $groupExpenses->first();
                $employee = $firstExpense->user;
                $month = $firstExpense->expense_month;
                $headquarter = $firstExpense->headquarter;
            @endphp
            
            <x-form id="approve-expense-form-{{ $groupKey }}" class="expense-approve-form">
                <div class="add-client bg-white rounded mb-4">
                    <!-- Header Section -->
                    <div class="p-20 border-bottom-grey">
                        <div class="text-center mb-3">
                            <h3 class="mb-0 font-weight-bold">RYVA VITABOTICS</h3>
                            <h4 class="mb-0">EXPENSES STATEMENT</h4>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="employee_name_{{ $groupKey }}" :fieldLabel="__('Name of Employee')">
                                </x-forms.label>
                                <div class="form-control height-35 f-14 bg-light">
                                    <strong>{{ $employee->name }}</strong>
                                    @if($employee->employeeDetail && $employee->employeeDetail->designation)
                                        <br><small class="text-muted">{{ $employee->employeeDetail->designation->name }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <x-forms.label class="mt-3" fieldId="headquarter_{{ $groupKey }}" :fieldLabel="__('Head Quarter')">
                                </x-forms.label>
                                <div class="form-control height-35 f-14 bg-light">
                                    <strong>{{ $headquarter->name ?? '-' }}</strong>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <x-forms.label class="mt-3" fieldId="month_{{ $groupKey }}" :fieldLabel="__('Month')">
                                </x-forms.label>
                                <div class="form-control height-35 f-14 bg-light">
                                    <strong>{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</strong>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <x-forms.label class="mt-3" fieldId="posted_on_{{ $groupKey }}" :fieldLabel="__('Posted on')">
                                </x-forms.label>
                                <div class="form-control height-35 f-14 bg-light">
                                    <strong>{{ $firstExpense->posted_on ? \Carbon\Carbon::parse($firstExpense->posted_on)->format(company()->date_format) : '-' }}</strong>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <x-forms.label class="mt-3" fieldId="no_of_vouchers_{{ $groupKey }}" :fieldLabel="__('NO. OF VOUCHERS ATTACHED')">
                                </x-forms.label>
                                <div class="form-control height-35 f-14 bg-light">
                                    <strong>{{ $firstExpense->no_of_vouchers ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Table Section -->
                    <div class="p-20">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="pharma-expense-table-{{ $groupKey }}">
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
                                        <th>
                                            Fixed Expenses
                                            <button type="button" class="btn btn-sm btn-link p-0 ml-2 copy-fixed-expenses" 
                                                    title="Copy Fixed Expenses to all rows" style="font-size: 12px; color: #007bff;">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </th>
                                        <th>Other Expenses</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="expense-rows-{{ $groupKey }}">
                                    @foreach($groupExpenses as $expense)
                                        <tr class="expense-row" data-expense-id="{{ $expense->id }}">
                                            <td>
                                                <input type="hidden" name="expenses[{{ $expense->id }}][id]" value="{{ $expense->id }}">
                                                <input type="hidden" name="expenses[{{ $expense->id }}][date]" value="{{ $expense->purchase_date->format('Y-m-d') }}">
                                                <input type="hidden" name="expenses[{{ $expense->id }}][day]" value="{{ $expense->day }}">
                                                {{ $expense->purchase_date->format(company()->date_format) }}
                                            </td>
                                            <td><strong>{{ $expense->day }}</strong></td>
                                            <td>
                                                <select class="form-control select-picker town-worked-select" 
                                                        name="expenses[{{ $expense->id }}][town_worked]" 
                                                        data-live-search="true">
                                                    <option value="">--</option>
                                                    @foreach($headquarters as $hq)
                                                        @if($hq->id == ($expense->headquarter_id ?? $headquarter->id))
                                                            <option value="{{ $hq->name }}" {{ $expense->town_worked == $hq->name ? 'selected' : '' }}>{{ $hq->name }} (Headquarter)</option>
                                                            @foreach($hq->exstations as $ex)
                                                                <option value="{{ $ex->name }}" {{ $expense->town_worked == $ex->name ? 'selected' : '' }}>{{ $ex->name }} (Ex Station)</option>
                                                            @endforeach
                                                            @foreach($hq->outstations as $os)
                                                                <option value="{{ $os->name }}" {{ $expense->town_worked == $os->name ? 'selected' : '' }}>{{ $os->name }} (Out Station)</option>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control select-picker worked-with-select" 
                                                        name="expenses[{{ $expense->id }}][worked_with][]" 
                                                        multiple data-live-search="true" 
                                                        data-actions-box="true" 
                                                        data-select-all-text="Select All" 
                                                        data-deselect-all-text="Deselect All"
                                                        data-selected-text-format="count > 3"
                                                        data-count-selected-text="{0} selected">
                                                    @php
                                                        $workedWith = $expense->worked_with ? json_decode($expense->worked_with, true) : [];
                                                        if (!is_array($workedWith)) $workedWith = [];
                                                    @endphp
                                                    @foreach($workedWithDesignations as $designation)
                                                        <option value="{{ $designation }}" {{ in_array($designation, $workedWith) ? 'selected' : '' }}>{{ $designation }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][no_of_doctors_met]" 
                                                       step="1" min="0" value="{{ $expense->no_of_doctors_met ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][no_of_retailers_met]" 
                                                       step="1" min="0" value="{{ $expense->no_of_retailers_met ?? 0 }}">
                                            </td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <select class="form-control select-picker" 
                                                                name="expenses[{{ $expense->id }}][headquarter_from]">
                                                            <option value="">--</option>
                                                            @foreach($headquarters as $hq)
                                                                @if($hq->id == ($expense->headquarter_id ?? $headquarter->id))
                                                                    <option value="{{ $hq->name }}" {{ $expense->headquarter_from == $hq->name ? 'selected' : '' }}>{{ $hq->name }}</option>
                                                                    @foreach($hq->exstations as $ex)
                                                                        <option value="{{ $ex->name }}" {{ $expense->headquarter_from == $ex->name ? 'selected' : '' }}>{{ $ex->name }}</option>
                                                                    @endforeach
                                                                    @foreach($hq->outstations as $os)
                                                                        <option value="{{ $os->name }}" {{ $expense->headquarter_from == $os->name ? 'selected' : '' }}>{{ $os->name }}</option>
                                                                    @endforeach
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <select class="form-control select-picker" 
                                                                name="expenses[{{ $expense->id }}][headquarter_to]">
                                                            <option value="">--</option>
                                                            @foreach($headquarters as $hq)
                                                                @if($hq->id == ($expense->headquarter_id ?? $headquarter->id))
                                                                    <option value="{{ $hq->name }}" {{ $expense->headquarter_to == $hq->name ? 'selected' : '' }}>{{ $hq->name }}</option>
                                                                    @foreach($hq->exstations as $ex)
                                                                        <option value="{{ $ex->name }}" {{ $expense->headquarter_to == $ex->name ? 'selected' : '' }}>{{ $ex->name }}</option>
                                                                    @endforeach
                                                                    @foreach($hq->outstations as $os)
                                                                        <option value="{{ $os->name }}" {{ $expense->headquarter_to == $os->name ? 'selected' : '' }}>{{ $os->name }}</option>
                                                                    @endforeach
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="form-control" name="expenses[{{ $expense->id }}][mode_of_transport]">
                                                    <option value="">--</option>
                                                    <option value="Bus" {{ $expense->mode_of_transport == 'Bus' ? 'selected' : '' }}>Bus</option>
                                                    <option value="Train" {{ $expense->mode_of_transport == 'Train' ? 'selected' : '' }}>Train</option>
                                                    <option value="Car" {{ $expense->mode_of_transport == 'Car' ? 'selected' : '' }}>Car</option>
                                                    <option value="Bike" {{ $expense->mode_of_transport == 'Bike' ? 'selected' : '' }}>Bike</option>
                                                    <option value="Auto" {{ $expense->mode_of_transport == 'Auto' ? 'selected' : '' }}>Auto</option>
                                                    <option value="Taxi" {{ $expense->mode_of_transport == 'Taxi' ? 'selected' : '' }}>Taxi</option>
                                                    <option value="Flight" {{ $expense->mode_of_transport == 'Flight' ? 'selected' : '' }}>Flight</option>
                                                    <option value="Other" {{ $expense->mode_of_transport == 'Other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][km]" 
                                                       step="0.01" min="0" value="{{ $expense->km ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][fare_rs]" 
                                                       step="0.01" min="0" value="{{ $expense->fare_rs ?? 0 }}">
                                            </td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <input type="number" class="form-control" 
                                                               name="expenses[{{ $expense->id }}][daily_allowance_hq_rs]" 
                                                               step="0.01" min="0" value="{{ $expense->daily_allowance_hq_rs ?? 0 }}" 
                                                               placeholder="HQ">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" class="form-control" 
                                                               name="expenses[{{ $expense->id }}][daily_allowance_ex_rs]" 
                                                               step="0.01" min="0" value="{{ $expense->daily_allowance_ex_rs ?? 0 }}" 
                                                               placeholder="Ex">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" class="form-control" 
                                                               name="expenses[{{ $expense->id }}][daily_allowance_os_rs]" 
                                                               step="0.01" min="0" value="{{ $expense->daily_allowance_os_rs ?? 0 }}" 
                                                               placeholder="O/S">
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][fixed_expenses]" 
                                                       step="0.01" min="0" value="{{ $expense->fixed_expenses ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" 
                                                       name="expenses[{{ $expense->id }}][other_expenses]" 
                                                       step="0.01" min="0" value="{{ $expense->other_expenses ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm remarks-input" 
                                                       name="expenses[{{ $expense->id }}][remarks]" 
                                                       value="{{ $expense->remarks ?? '' }}" 
                                                       placeholder="Remarks">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot id="expense-totals-{{ $groupKey }}">
                                    <tr class="bg-light font-weight-bold" style="border-top: 2px solid #333;">
                                        <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                                        <td class="text-right"><span class="total-doctors">0</span></td>
                                        <td class="text-right"><span class="total-retailers">0</span></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-right">
                                            <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Km.</div>
                                            <div><span class="total-km" style="font-weight: bold;">0.00</span></div>
                                        </td>
                                        <td class="text-right">
                                            <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Fare</div>
                                            <div><span class="total-fare" style="font-weight: bold;">0.00</span></div>
                                        </td>
                                        <td style="padding: 8px;">
                                            <div class="row" style="margin: 0;">
                                                <div class="col-4 text-right" style="padding: 0 8px; border-right: 1px solid #ccc;">
                                                    <div style="font-size: 13px; color: #666; margin-bottom: 2px;">HQ. Rs</div>
                                                    <div><span class="total-hq-rs" style="font-weight: bold;">0.00</span></div>
                                                </div>
                                                <div class="col-4 text-right" style="padding: 0 8px; border-right: 1px solid #ccc;">
                                                    <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Ex Rs.</div>
                                                    <div><span class="total-ex-rs" style="font-weight: bold;">0.00</span></div>
                                                </div>
                                                <div class="col-4 text-right" style="padding: 0 8px;">
                                                    <div style="font-size: 13px; color: #666; margin-bottom: 2px;">O/S Rs</div>
                                                    <div><span class="total-os-rs" style="font-weight: bold;">0.00</span></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Fixed</div>
                                            <div><span class="total-fixed" style="font-weight: bold;">0.00</span></div>
                                        </td>
                                        <td class="text-right">
                                            <div style="font-size: 13px; color: #666; margin-bottom: 2px;">Other</div>
                                            <div><span class="total-other" style="font-weight: bold;">0.00</span></div>
                                        </td>
                                        <td></td>
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
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">Activity Summary</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total Days:</span>
                                            <strong class="summary-total-days">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Doctors Met:</span>
                                            <strong class="summary-total-doctors">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Retailers Met:</span>
                                            <strong class="summary-total-retailers">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Total Km:</span>
                                            <strong class="summary-total-km">0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">Expense Breakdown</h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Fare:</span>
                                            <strong class="summary-total-fare">₹0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Daily Allowances:</span>
                                            <strong class="summary-total-allowances">₹0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Fixed Expenses:</span>
                                            <strong class="summary-total-fixed">₹0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Other Expenses:</span>
                                            <strong class="summary-total-other">₹0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-lg h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                    <div class="card-body d-flex flex-column justify-content-center">
                                        <h6 class="card-title mb-3" style="color: rgba(255,255,255,0.9);">Total Expense</h6>
                                        <div class="text-center">
                                            <h2 class="mb-0 font-weight-bold summary-grand-total">₹0.00</h2>
                                            <small style="color: rgba(255,255,255,0.8);">Grand Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">Daily Allowances Breakdown</h6>
                                        <div class="row">
                                            <div class="col-md-4 text-center border-right">
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">HQ. Rs</small>
                                                    <strong class="h5 summary-hq-rs">₹0.00</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center border-right">
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Ex Rs.</small>
                                                    <strong class="h5 summary-ex-rs">₹0.00</strong>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-center">
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">O/S Rs</small>
                                                    <strong class="h5 summary-os-rs">₹0.00</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Display Section -->
                    @if($firstExpense->bill)
                        <div class="p-20 border-top-grey">
                            <h5 class="mb-3 font-weight-bold">VOUCHER ATTACHMENTS</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="{{ $firstExpense->bill_url }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa fa-file-pdf"></i> View Voucher
                                    </a>
                                    @php
                                        $additionalVouchers = $firstExpense->description ? json_decode($firstExpense->description, true) : [];
                                        if (isset($additionalVouchers['additional_vouchers']) && is_array($additionalVouchers['additional_vouchers'])) {
                                            foreach($additionalVouchers['additional_vouchers'] as $voucher) {
                                                echo '<a href="' . asset_url_local_s3('expense-invoice/' . $voucher) . '" target="_blank" class="btn btn-sm btn-primary ml-2"><i class="fa fa-file"></i> View Voucher ' . (array_search($voucher, $additionalVouchers['additional_vouchers']) + 2) . '</a>';
                                            }
                                        }
                                    @endphp
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-form-actions>
                        <input type="hidden" name="expense_group_key" value="{{ $groupKey }}">
                        <input type="hidden" name="expense_ids" value="{{ $groupExpenses->pluck('id')->toJson() }}">
                        <button type="button" class="btn-primary rounded f-14 p-2 mr-3 approve-expense-btn" 
                                data-expense-ids="{{ json_encode($groupExpenses->pluck('id')->values()->all()) }}">
                            <i class="fa fa-check mr-1"></i> Approve
                        </button>
                        <button type="button" class="btn-danger rounded f-14 p-2 mr-3 reject-expense-btn" 
                                data-expense-ids="{{ json_encode($groupExpenses->pluck('id')->values()->all()) }}">
                            <i class="fa fa-times mr-1"></i> @lang('app.reject')
                        </button>
                        
                        @php
                            $deletePermission = user()->permission('delete_expenses');
                            $hasPendingOrApproved = $groupExpenses->whereIn('status', ['pending', 'approved'])->isNotEmpty();
                            $canDelete = false;
                            
                            if ($hasPendingOrApproved) {
                                // Only admin can delete pending/approved expenses
                                $canDelete = $deletePermission == 'all';
                            } else {
                                // Normal delete permission check for other expenses
                                $canDelete = $deletePermission == 'all' || ($deletePermission == 'added' && $groupExpenses->every(function($exp) { return $exp->added_by == user()->id; }));
                            }
                        @endphp
                        
                        @if($canDelete)
                            <button type="button" class="btn-danger rounded f-14 p-2 mr-3 delete-expense-group-btn" 
                                    data-expense-ids="{{ json_encode($groupExpenses->pluck('id')->values()->all()) }}"
                                    data-employee-name="{{ $employee->name }}"
                                    data-month="{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}">
                                <i class="fa fa-trash mr-1"></i> Delete
                            </button>
                        @endif
                        
                        <x-forms.button-cancel :link="route('expenses.status')" class="border-0">@lang('app.cancel')
                        </x-forms.button-cancel>
                    </x-form-actions>
                </div>
            </x-form>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize select pickers
    $('.select-picker').selectpicker();
    
    // Initialize worked-with selectpickers with actionsBox
    $('.worked-with-select').each(function() {
        $(this).selectpicker({
            actionsBox: true,
            selectAllText: 'Select All',
            deselectAllText: 'Deselect All',
            selectedTextFormat: 'count > 3',
            countSelectedText: '{0} selected'
        });
    });
    
    // Function to calculate totals for each form
    function calculateTotals(formId) {
        const form = formId ? $('#' + formId) : $(this).closest('form');
        const tbody = form.find('tbody');
        const tfoot = form.find('tfoot');
        
        let totalDoctors = 0;
        let totalRetailers = 0;
        let totalKm = 0;
        let totalFare = 0;
        let totalHqRs = 0;
        let totalExRs = 0;
        let totalOsRs = 0;
        let totalFixed = 0;
        let totalOther = 0;
        
        tbody.find('.expense-row').each(function() {
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
        
        const grandTotal = totalFare + totalHqRs + totalExRs + totalOsRs + totalFixed + totalOther;
        const totalAllowances = totalHqRs + totalExRs + totalOsRs;
        const totalDays = tbody.find('.expense-row').length;
        
        // Update totals in tfoot
        tfoot.find('.total-doctors').text(totalDoctors);
        tfoot.find('.total-retailers').text(totalRetailers);
        tfoot.find('.total-km').text(totalKm.toFixed(2));
        tfoot.find('.total-fare').text(totalFare.toFixed(2));
        tfoot.find('.total-hq-rs').text(totalHqRs.toFixed(2));
        tfoot.find('.total-ex-rs').text(totalExRs.toFixed(2));
        tfoot.find('.total-os-rs').text(totalOsRs.toFixed(2));
        tfoot.find('.total-fixed').text(totalFixed.toFixed(2));
        tfoot.find('.total-other').text(totalOther.toFixed(2));
        
        // Update summary cards
        form.find('.summary-total-days').text(totalDays);
        form.find('.summary-total-doctors').text(totalDoctors);
        form.find('.summary-total-retailers').text(totalRetailers);
        form.find('.summary-total-km').text(totalKm.toFixed(2));
        form.find('.summary-total-fare').text('₹' + totalFare.toFixed(2));
        form.find('.summary-total-allowances').text('₹' + totalAllowances.toFixed(2));
        form.find('.summary-total-fixed').text('₹' + totalFixed.toFixed(2));
        form.find('.summary-total-other').text('₹' + totalOther.toFixed(2));
        form.find('.summary-grand-total').text('₹' + grandTotal.toFixed(2));
        form.find('.summary-hq-rs').text('₹' + totalHqRs.toFixed(2));
        form.find('.summary-ex-rs').text('₹' + totalExRs.toFixed(2));
        form.find('.summary-os-rs').text('₹' + totalOsRs.toFixed(2));
    }
    
    // Calculate totals on input change
    $(document).on('input change', '.expense-approve-form input[type="number"], .expense-approve-form select', function() {
        const form = $(this).closest('form');
        calculateTotals(form.attr('id'));
    });
    
    // Calculate initial totals for all forms
    $('.expense-approve-form').each(function() {
        calculateTotals($(this).attr('id'));
    });
    
    // Copy Fixed Expenses functionality
    $(document).on('click', '.copy-fixed-expenses', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const tbody = form.find('tbody');
        
        let sourceValue = null;
        tbody.find('.expense-row').each(function() {
            const value = parseFloat($(this).find('input[name*="[fixed_expenses]"]').val()) || 0;
            if (value > 0) {
                sourceValue = value;
                return false;
            }
        });
        
        if (sourceValue === null) {
            Swal.fire({
                icon: 'info',
                title: 'No Value Found',
                text: 'Please enter a Fixed Expenses value in at least one row first.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        Swal.fire({
            title: 'Copy Fixed Expenses?',
            text: `Do you want to copy ₹${sourceValue.toFixed(2)} to all rows?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Copy to All',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                tbody.find('.expense-row').each(function() {
                    $(this).find('input[name*="[fixed_expenses]"]').val(sourceValue);
                });
                calculateTotals(form.attr('id'));
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: `Fixed Expenses value ₹${sourceValue.toFixed(2)} has been copied to all rows.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // Approve expense form
    $(document).on('click', '.approve-expense-btn', function(e) {
        e.preventDefault();
        console.log('Approve button clicked');
        const btn = $(this);
        const form = btn.closest('form');
        // Use attr() instead of data() to get raw value, then parse manually
        const expenseIdsData = btn.attr('data-expense-ids');
        console.log('Expense IDs data (raw):', expenseIdsData);
        console.log('Button element:', btn[0]);
        console.log('Button attributes:', btn[0] ? btn[0].attributes : 'not found');
        
        let expenseIds;
        try {
            expenseIds = typeof expenseIdsData === 'string' ? JSON.parse(expenseIdsData) : expenseIdsData;
            // Ensure it's always an array (handle case where single ID is returned as number)
            if (!Array.isArray(expenseIds)) {
                expenseIds = [expenseIds];
            }
            console.log('Parsed expense IDs:', expenseIds);
        } catch (error) {
            console.error('Error parsing expense IDs:', error);
            Swal.fire('Error', 'Invalid expense data. Please refresh the page and try again.', 'error');
            return;
        }
        
        if (!expenseIds || expenseIds.length === 0) {
            Swal.fire('Error', 'No expenses found to approve.', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Approve Expenses?',
            text: `Are you sure you want to approve ${expenseIds.length} expense(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect form data properly
                const formData = new FormData();
                formData.append('expense_ids', JSON.stringify(expenseIds));
                formData.append('_token', '{{ csrf_token() }}');
                
                // Collect all expense data
                const expenses = {};
                let hasData = false;
                form.find('.expense-row').each(function() {
                    const expenseId = $(this).data('expense-id');
                    if (expenseId) {
                        hasData = true;
                        // Get worked_with as array (multi-select)
                        const workedWithSelect = $(this).find('select[name*="[worked_with]"]');
                        let workedWith = workedWithSelect.val();
                        // Handle Bootstrap Select multi-select
                        if (workedWithSelect.hasClass('selectpicker')) {
                            workedWith = workedWithSelect.selectpicker('val');
                        }
                        const workedWithArray = Array.isArray(workedWith) ? workedWith : (workedWith ? [workedWith] : []);
                        
                        expenses[expenseId] = {
                            town_worked: $(this).find('select[name*="[town_worked]"]').val() || '',
                            worked_with: workedWithArray,
                            no_of_doctors_met: parseInt($(this).find('input[name*="[no_of_doctors_met]"]').val()) || 0,
                            no_of_retailers_met: parseInt($(this).find('input[name*="[no_of_retailers_met]"]').val()) || 0,
                            headquarter_from: $(this).find('select[name*="[headquarter_from]"]').val() || '',
                            headquarter_to: $(this).find('select[name*="[headquarter_to]"]').val() || '',
                            mode_of_transport: $(this).find('select[name*="[mode_of_transport]"]').val() || '',
                            km: parseFloat($(this).find('input[name*="[km]"]').val()) || 0,
                            fare_rs: parseFloat($(this).find('input[name*="[fare_rs]"]').val()) || 0,
                            daily_allowance_hq_rs: parseFloat($(this).find('input[name*="[daily_allowance_hq_rs]"]').val()) || 0,
                            daily_allowance_ex_rs: parseFloat($(this).find('input[name*="[daily_allowance_ex_rs]"]').val()) || 0,
                            daily_allowance_os_rs: parseFloat($(this).find('input[name*="[daily_allowance_os_rs]"]').val()) || 0,
                            fixed_expenses: parseFloat($(this).find('input[name*="[fixed_expenses]"]').val()) || 0,
                            other_expenses: parseFloat($(this).find('input[name*="[other_expenses]"]').val()) || 0,
                            remarks: $(this).find('input[name*="[remarks]"]').val() || ''
                        };
                    }
                });
                
                if (!hasData) {
                    Swal.fire('Error', 'No expense data found in the form.', 'error');
                    return;
                }
                
                // Debug: Log the data being sent
                console.log('Expense IDs:', expenseIds);
                console.log('Expenses Data:', expenses);
                console.log('Form Data Keys:', Object.keys(expenses));
                
                formData.append('expenses', JSON.stringify(expenses));
                
                console.log('About to send AJAX request');
                console.log('URL:', "{{ route('expenses.approve-all') }}");
                console.log('Expense IDs:', expenseIds);
                console.log('Expenses data keys:', Object.keys(expenses));
                
                // Use standard jQuery AJAX for more control
                $.ajax({
                    url: "{{ route('expenses.approve-all') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        btn.prop('disabled', true);
                        console.log('AJAX request started');
                    },
                    success: function(response) {
                        console.log('AJAX Success - Full response:', response);
                        btn.prop('disabled', false);
                        
                        // Show toast notification
                        if (response && response.message) {
                            if (typeof $.showToastr !== 'undefined') {
                                $.showToastr(response.message, 'success');
                            }
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: (response && response.message) ? response.message : `${expenseIds.length} expense(s) approved successfully`,
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            // Redirect to status page so employee can see the updated status
                            window.location.href = "{{ route('expenses.status') }}?month={{ $currentMonth }}";
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error - Status:', status);
                        console.error('AJAX Error - Error:', error);
                        console.error('AJAX Error - XHR:', xhr);
                        console.error('AJAX Error - Response Text:', xhr.responseText);
                        btn.prop('disabled', false);
                        
                        let errorMessage = 'Failed to approve expenses';
                        if (xhr.responseJSON) {
                            console.error('Response JSON:', xhr.responseJSON);
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                        } else if (xhr.responseText) {
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                errorMessage = errorData.message || errorData.error || errorMessage;
                            } catch (e) {
                                errorMessage = xhr.responseText.substring(0, 200);
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function() {
                        console.log('AJAX request completed');
                    }
                });
            }
        });
    });

    // Reject expense form (optional reason)
    $(document).on('click', '.reject-expense-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const expenseIdsData = btn.attr('data-expense-ids');
        let expenseIds;
        try {
            expenseIds = typeof expenseIdsData === 'string' ? JSON.parse(expenseIdsData) : expenseIdsData;
            if (!Array.isArray(expenseIds)) {
                expenseIds = [expenseIds];
            }
        } catch (err) {
            Swal.fire('Error', 'Invalid expense data. Please refresh and try again.', 'error');
            return;
        }
        if (!expenseIds || expenseIds.length === 0) {
            Swal.fire('Error', 'No expenses found to reject.', 'error');
            return;
        }
        Swal.fire({
            title: '@lang("app.reject") ' + expenseIds.length + ' expense(s)?',
            text: 'You can optionally provide a reason (employee will see it).',
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Reason for rejection (optional)',
            inputAttributes: { 'rows': 3 },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '@lang("app.reject")',
            cancelButtonText: '@lang("app.cancel")'
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = (result.value && result.value.trim()) ? result.value.trim() : '';
                btn.prop('disabled', true);
                $.easyAjax({
                    type: 'POST',
                    url: "{{ route('expenses.reject-all') }}",
                    data: {
                        _token: '{{ csrf_token() }}',
                        expense_ids: JSON.stringify(expenseIds),
                        reject_reason: reason
                    },
                    success: function(response) {
                        if (response && response.status === 'success') {
                            if (typeof $.showToastr !== 'undefined') {
                                $.showToastr(response.message || '@lang("app.expenseRejected")', 'success');
                            }
                            Swal.fire({
                                icon: 'success',
                                title: '@lang("app.rejected")',
                                text: (response && response.message) ? response.message : (expenseIds.length + ' expense(s) rejected.'),
                                confirmButtonText: 'OK',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(function() {
                                window.location.href = "{{ route('expenses.index') }}?month={{ $currentMonth }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false);
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to reject expenses.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    }
                });
            }
        });
    });
    
    // Delete expense group
    $(document).on('click', '.delete-expense-group-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const expenseIdsData = btn.attr('data-expense-ids');
        const employeeName = btn.attr('data-employee-name');
        const month = btn.attr('data-month');
        
        let expenseIds;
        try {
            expenseIds = typeof expenseIdsData === 'string' ? JSON.parse(expenseIdsData) : expenseIdsData;
            if (!Array.isArray(expenseIds)) {
                expenseIds = [expenseIds];
            }
        } catch (error) {
            console.error('Error parsing expense IDs:', error);
            Swal.fire('Error', 'Invalid expense data. Please refresh the page and try again.', 'error');
            return;
        }
        
        if (!expenseIds || expenseIds.length === 0) {
            Swal.fire('Error', 'No expenses found to delete.', 'error');
            return;
        }
        
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: `Are you sure you want to delete ${expenseIds.length} expense(s) for ${employeeName} (${month})? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-danger mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Delete expenses one by one
                let deletedCount = 0;
                let failedCount = 0;
                const totalCount = expenseIds.length;
                
                // Disable button during deletion
                btn.prop('disabled', true);
                
                // Function to delete a single expense
                function deleteExpense(expenseId, index) {
                    const url = "{{ route('expenses.destroy', ':id') }}".replace(':id', expenseId);
                    const token = "{{ csrf_token() }}";
                    
                    console.log('Deleting expense ID:', expenseId, 'Index:', index);
                    console.log('Delete URL:', url);
                    
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            console.log('Delete success response for expense', expenseId, ':', response);
                            if (response && response.status == "success") {
                                deletedCount++;
                                console.log(`Deleted ${deletedCount}/${totalCount} expenses`);
                                
                                // Check if all deletions are complete
                                if (deletedCount + failedCount === totalCount) {
                                    btn.prop('disabled', false);
                                    if (failedCount === 0) {
                                        if (typeof $.showToastr !== 'undefined') {
                                            $.showToastr('All expenses deleted successfully', 'success');
                                        }
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: `${deletedCount} expense(s) deleted successfully.`,
                                            confirmButtonText: 'OK',
                                            timer: 2000,
                                            timerProgressBar: true
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Partially Completed',
                                            text: `${deletedCount} expense(s) deleted, ${failedCount} failed.`,
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    }
                                }
                            } else {
                                // Response indicates failure
                                failedCount++;
                                console.error('Delete failed for expense', expenseId, '- response:', response);
                                if (deletedCount + failedCount === totalCount) {
                                    btn.prop('disabled', false);
                                    handleDeleteCompletion();
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            failedCount++;
                            console.error('Error deleting expense ID:', expenseId);
                            console.error('XHR:', xhr);
                            console.error('Status:', status);
                            console.error('Error:', error);
                            console.error('Response Text:', xhr.responseText);
                            
                            if (deletedCount + failedCount === totalCount) {
                                btn.prop('disabled', false);
                                handleDeleteCompletion();
                            }
                        }
                    });
                }
                
                // Helper function to handle completion
                function handleDeleteCompletion() {
                    console.log(`Delete completed: ${deletedCount} succeeded, ${failedCount} failed`);
                    if (deletedCount === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete expenses. Please check console for details and try again.',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Partially Completed',
                            text: `${deletedCount} expense(s) deleted successfully, ${failedCount} failed.`,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                }
                
                // Delete all expenses sequentially
                expenseIds.forEach(function(expenseId, index) {
                    setTimeout(function() {
                        deleteExpense(expenseId, index);
                    }, index * 100); // Small delay between requests
                });
            }
        });
    });
});
</script>
@endpush

