@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar mb-3">
            <div class="d-flex align-items-center">
                <h4 class="mb-0 mr-3">Full & Final Settlement - {{ $fnfSettlement->employee->name }}</h4>
                <span class="badge badge-{{ $fnfSettlement->status_color }} f-14 p-2">
                    {{ ucfirst(str_replace('_', ' ', $fnfSettlement->status)) }}
                </span>
            </div>
            <div class="d-flex align-items-center">
                @if($fnfSettlement->status != 'completed' && $fnfSettlement->status != 'cancelled')
                    <button type="button" class="btn btn-success mr-2" id="approve-fnf">
                        <i class="fa fa-check"></i> Approve & Complete
                    </button>
                @endif
                <a href="{{ route('fnf-settlements.download-statement', $fnfSettlement->id) }}" 
                   class="btn btn-primary mr-2">
                    <i class="fa fa-download"></i> Download Statement
                </a>
                <x-forms.link-secondary :link="route('fnf-settlements.index')" icon="arrow-left">
                    @lang('app.back')
                </x-forms.link-secondary>
            </div>
        </div>

        <div class="row">
            <!-- Employee Info Card -->
            <div class="col-md-4">
                <div class="card border-0 b-shadow-4 mb-3">
                    <div class="card-header bg-white border-bottom-grey">
                        <h5 class="mb-0">Employee Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ $fnfSettlement->employee->image_url }}" 
                                 class="rounded-circle" 
                                 style="width: 80px; height: 80px;" 
                                 alt="{{ $fnfSettlement->employee->name }}">
                            <h5 class="mt-2">{{ $fnfSettlement->employee->name }}</h5>
                            <p class="text-muted f-13">{{ $fnfSettlement->employee->email }}</p>
                        </div>
                        <table class="table table-borderless f-13">
                            <tr>
                                <td class="text-muted">Employee ID:</td>
                                <td class="text-right"><strong>{{ $fnfSettlement->employee->employeeDetail->employee_id ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Department:</td>
                                <td class="text-right"><strong>{{ $fnfSettlement->employee->employeeDetail->designation->name ?? '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Resignation Type:</td>
                                <td class="text-right"><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $fnfSettlement->resignation_type)) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Resignation Date:</td>
                                <td class="text-right"><strong>{{ $fnfSettlement->resignation_date ? $fnfSettlement->resignation_date->format(company()->date_format) : '-' }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Last Working Day:</td>
                                <td class="text-right"><strong>{{ $fnfSettlement->last_working_day->format(company()->date_format) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Financial Summary Card -->
                <div class="card border-0 b-shadow-4">
                    <div class="card-header bg-white border-bottom-grey">
                        <h5 class="mb-0">Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless f-13">
                            <tr>
                                <td class="text-muted">Gross Amount:</td>
                                <td class="text-right text-success"><strong>{{ currency_format($fnfSettlement->gross_amount, company()->currency_id) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Total Deductions:</td>
                                <td class="text-right text-danger"><strong>{{ currency_format($fnfSettlement->total_deductions, company()->currency_id) }}</strong></td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-dark"><strong>Net Payable:</strong></td>
                                <td class="text-right"><h5 class="text-success mb-0">{{ currency_format($fnfSettlement->net_payable, company()->currency_id) }}</h5></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Payment Status:</td>
                                <td class="text-right">
                                    @if($fnfSettlement->payment_status == 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($fnfSettlement->payment_status == 'processed')
                                        <span class="badge badge-info">Processed</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @if($fnfSettlement->payment_date)
                            <tr>
                                <td class="text-muted">Payment Date:</td>
                                <td class="text-right"><strong>{{ $fnfSettlement->payment_date->format(company()->date_format) }}</strong></td>
                            </tr>
                            @endif
                        </table>

                        @if($fnfSettlement->payment_status != 'paid' && $fnfSettlement->status == 'completed')
                        <button type="button" class="btn btn-block btn-primary" id="mark-payment-complete">
                            <i class="fa fa-check"></i> Mark Payment Complete
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-8">
                
                <!-- Clearance Checklist -->
                <div class="card border-0 b-shadow-4 mb-3">
                    <div class="card-header bg-white border-bottom-grey">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Clearance Checklist</h5>
                            <div class="progress" style="width: 200px; height: 25px;">
                                <div class="progress-bar progress-bar-striped bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $fnfSettlement->clearance_progress }}%;" 
                                     aria-valuenow="{{ $fnfSettlement->clearance_progress }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $fnfSettlement->clearance_progress }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @foreach($fnfSettlement->clearance_checklist as $index => $department)
                        <div class="clearance-department mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">
                                    @if($department['cleared'])
                                        <i class="fa fa-check-circle text-success"></i>
                                    @else
                                        <i class="fa fa-circle-o text-muted"></i>
                                    @endif
                                    {{ $department['department'] }}
                                </h6>
                                @if(!$department['cleared'])
                                <button type="button" class="btn btn-sm btn-success mark-cleared" 
                                        data-index="{{ $index }}">
                                    <i class="fa fa-check"></i> Mark Cleared
                                </button>
                                @endif
                            </div>
                            
                            <ul class="list-unstyled ml-4 f-13">
                                @foreach($department['items'] as $item)
                                <li class="mb-1">
                                    <i class="fa fa-{{ $department['cleared'] ? 'check text-success' : 'minus text-muted' }}"></i>
                                    {{ $item }}
                                </li>
                                @endforeach
                            </ul>

                            @if($department['cleared'])
                            <div class="bg-light p-2 rounded f-12 ml-4">
                                <strong>Cleared by:</strong> {{ $department['cleared_by'] ?? 'System' }} on {{ $department['cleared_date'] }}
                                @if($department['remarks'])
                                <br><strong>Remarks:</strong> {{ $department['remarks'] }}
                                @endif
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="card border-0 b-shadow-4 mb-3">
                    <div class="card-header bg-white border-bottom-grey">
                        <h5 class="mb-0">Financial Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-success mb-3">Earnings</h6>
                        <table class="table table-sm f-13">
                            <tr>
                                <td>Basic Salary ({{ $fnfSettlement->payable_days }} days of {{ $fnfSettlement->working_days }} working days)</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->earned_salary, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Leave Encashment ({{ $fnfSettlement->leave_balance_days }} days)</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->leave_encashment_amount, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Pending Bonus</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->pending_bonus, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Pending Incentives</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->pending_incentives, company()->currency_id) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Total Earnings:</strong></td>
                                <td class="text-right"><strong class="text-success">{{ currency_format($fnfSettlement->gross_amount, company()->currency_id) }}</strong></td>
                            </tr>
                        </table>

                        <h6 class="text-danger mb-3 mt-4">Deductions</h6>
                        <table class="table table-sm f-13">
                            <tr>
                                <td>Loan Outstanding</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->loan_outstanding, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Advance Outstanding</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->advance_outstanding, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Notice Period Recovery</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->notice_period_recovery, company()->currency_id) }}</td>
                            </tr>
                            <tr>
                                <td>Other Deductions</td>
                                <td class="text-right">{{ currency_format($fnfSettlement->other_deductions, company()->currency_id) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Total Deductions:</strong></td>
                                <td class="text-right"><strong class="text-danger">{{ currency_format($fnfSettlement->total_deductions, company()->currency_id) }}</strong></td>
                            </tr>
                        </table>

                        @if($fnfSettlement->deduction_remarks)
                        <div class="alert alert-info f-13 mt-3">
                            <strong>Deduction Remarks:</strong> {{ $fnfSettlement->deduction_remarks }}
                        </div>
                        @endif

                        <div class="bg-success text-white p-3 rounded mt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Net Payable Amount:</h5>
                                <h4 class="mb-0">{{ currency_format($fnfSettlement->net_payable, company()->currency_id) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                @if($fnfSettlement->remarks || $fnfSettlement->hr_notes)
                <div class="card border-0 b-shadow-4">
                    <div class="card-header bg-white border-bottom-grey">
                        <h5 class="mb-0">Remarks & Notes</h5>
                    </div>
                    <div class="card-body">
                        @if($fnfSettlement->remarks)
                        <div class="mb-3">
                            <strong>Remarks:</strong>
                            <p class="f-13 mb-0">{{ $fnfSettlement->remarks }}</p>
                        </div>
                        @endif
                        @if($fnfSettlement->hr_notes)
                        <div>
                            <strong>HR Notes:</strong>
                            <p class="f-13 mb-0">{{ $fnfSettlement->hr_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Mark Clearance Modal -->
    <div class="modal fade" id="clearanceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Department Clearance</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="dept-index">
                    <x-forms.textarea :fieldLabel="'Clearance Remarks'" fieldName="clearance_remarks" 
                                      fieldId="clearance_remarks">
                    </x-forms.textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirm-clearance">
                        <i class="fa fa-check"></i> Confirm Clearance
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Payment Complete</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <x-forms.text fieldId="payment_date" :fieldLabel="'Payment Date'" 
                                  fieldName="payment_date" :fieldValue="now()->format(company()->date_format)" 
                                  fieldRequired="true"></x-forms.text>
                    
                    <x-forms.text fieldId="payment_mode" :fieldLabel="'Payment Mode'" 
                                  fieldName="payment_mode" fieldPlaceholder="e.g. Bank Transfer, Cheque"
                                  fieldRequired="true"></x-forms.text>
                    
                    <x-forms.text fieldId="payment_reference" :fieldLabel="'Payment Reference'" 
                                  fieldName="payment_reference" fieldPlaceholder="Transaction ID / Cheque No"></x-forms.text>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-payment">
                        <i class="fa fa-check"></i> Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Mark department clearance
            $('.mark-cleared').on('click', function() {
                let index = $(this).data('index');
                $('#dept-index').val(index);
                $('#clearanceModal').modal('show');
            });

            // Confirm clearance
            $('#confirm-clearance').on('click', function() {
                let index = $('#dept-index').val();
                let remarks = $('#clearance_remarks').val();

                $.easyAjax({
                    url: "{{ route('fnf-settlements.update-clearance', $fnfSettlement->id) }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        department_index: index,
                        cleared: 'true',
                        remarks: remarks
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#clearanceModal').modal('hide');
                            window.location.reload();
                        }
                    }
                });
            });

            // Mark payment complete
            $('#mark-payment-complete').on('click', function() {
                $('#paymentModal').modal('show');
                datepicker('#payment_date', {
                    position: 'bl',
                    ...datepickerConfig
                });
            });

            // Confirm payment
            $('#confirm-payment').on('click', function() {
                $.easyAjax({
                    url: "{{ route('fnf-settlements.mark-payment-complete', $fnfSettlement->id) }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        payment_date: $('#payment_date').val(),
                        payment_mode: $('#payment_mode').val(),
                        payment_reference: $('#payment_reference').val()
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#paymentModal').modal('hide');
                            window.location.reload();
                        }
                    }
                });
            });

            // Approve FNF
            $('#approve-fnf').on('click', function() {
                Swal.fire({
                    title: 'Approve FNF Settlement?',
                    text: "This will mark the FNF as completed and approved.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Approve!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.easyAjax({
                            url: "{{ route('fnf-settlements.approve', $fnfSettlement->id) }}",
                            type: "POST",
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.status === 'success') {
                                    window.location.reload();
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush

